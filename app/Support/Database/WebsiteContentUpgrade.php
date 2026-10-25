<?php

namespace App\Support\Database;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class WebsiteContentUpgrade
{
    public function run(): void
    {
        foreach (['contact_infos' => 'website_contact', 'about_infos' => 'website_about'] as $old => $new) {
            if (Schema::hasTable($old)) {
                if (Schema::hasTable($new)) {
                    throw new \RuntimeException("Both {$old} and {$new} exist. Resolve the conflicting tables before migrating.");
                }
                Schema::rename($old, $new);
            }
        }
        foreach (['website_url', 'email', 'phone', 'mobile', 'address', 'legacy_contact_details_backup'] as $field) {
            if (Schema::hasColumn('website_contact', $field)) {
                continue;
            }
            Schema::table('website_contact', function (Blueprint $table) use ($field) {
                match ($field) {
                    'website_url' => $table->string($field, 2048)->nullable(),
                    'phone', 'mobile' => $table->string($field, 50)->nullable(),
                    'address' => $table->text($field)->nullable(),
                    'legacy_contact_details_backup' => $table->json($field)->nullable(),
                    default => $table->string($field)->nullable(),
                };
            });
        }
        DB::transaction(function () {
            if (Schema::hasTable('website_contact_details')) {
                foreach (DB::table('website_contact_details')->orderBy('id')->get() as $profile) {
                    $backup = $profile->legacy_content_backup ? json_decode($profile->legacy_content_backup, true, flags: JSON_THROW_ON_ERROR) : null;
                    $target = isset($backup['id']) ? DB::table('website_contact')->find($backup['id']) : null;
                    $target ??= DB::table('website_contact')->orderByDesc('status')->orderBy('id')->first();
                    if (! $target) {
                        $id = DB::table('website_contact')->insertGetId([
                            'title' => 'What are you working on?', 'subtitle' => 'Share your idea, challenge, or next opportunity.',
                            'descriptions' => '', 'status' => $profile->status, 'created_at' => now(), 'updated_at' => now(),
                        ]);
                        $target = DB::table('website_contact')->find($id);
                    }
                    $values = [];
                    if (blank($target->legacy_contact_details_backup)) {
                        $values['legacy_contact_details_backup'] = json_encode((array) $profile, JSON_THROW_ON_ERROR);
                    }
                    foreach (['website_url', 'email', 'phone', 'mobile', 'address'] as $field) {
                        if (blank($target->{$field})) {
                            $values[$field] = $profile->{$field};
                        }
                    }
                    if ($values) {
                        DB::table('website_contact')->where('id', $target->id)->update($values);
                    }
                }
            }
            $this->extractLegacyDetails();
            $links = DB::table('links')->whereIn('url', ['website-contact-details', '/website-contact-details'])->pluck('id');
            DB::table('permissions')->whereIn('link_id', $links)->delete();
            DB::table('links')->whereIn('id', $links)->delete();
        });
        Schema::dropIfExists('website_contact_details');
    }

    private function extractLegacyDetails(): void
    {
        // Completed installations must not reinterpret later editorial content.
        if (DB::table('migrations')->whereIn('migration', [
            '2026_10_23_000003_merge_contact_details_into_website_contact',
            '2026_10_24_000002_upgrade_website_content',
        ])->exists()) {
            return;
        }
        foreach (DB::table('website_contact')->orderBy('id')->get() as $record) {
            $values = [];
            $cleaned = preg_replace_callback('~<h([2-4])\b[^>]*>\s*(Email|Address)\s*</h\1>\s*<p\b[^>]*>(.*?)</p>~is', function ($match) use (&$values, $record) {
                $field = strtolower($match[2]);
                if (filled($record->{$field}) || isset($values[$field])) {
                    return $match[0];
                }
                $value = trim(html_entity_decode(strip_tags(preg_replace('~<br\s*/?>~i', "\n", $match[3])), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
                if ($value === '' || ($field === 'email' && ! filter_var($value, FILTER_VALIDATE_EMAIL))) {
                    return $match[0];
                }
                $values[$field] = $value;

                return '';
            }, $record->descriptions);
            if ($values) {
                if (blank($record->legacy_contact_details_backup)) {
                    $values['legacy_contact_details_backup'] = json_encode(['id' => $record->id, 'original' => $record->descriptions, 'migrated' => $cleaned], JSON_THROW_ON_ERROR);
                }
                DB::table('website_contact')->where('id', $record->id)->update([...$values, 'descriptions' => $cleaned]);
            }
        }
    }
}
