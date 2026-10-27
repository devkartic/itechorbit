<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CorrectPublicContent extends Command
{
    protected $signature = 'website:correct-public-content {--apply : Back up and apply reviewed corrections} {--dry-run : Preview only (default)}';

    protected $description = 'Preview or apply fingerprint-checked public business copy corrections';

    public function handle(): int
    {
        if ($this->option('apply') && $this->option('dry-run')) {
            $this->error('Choose either --apply or --dry-run.');

            return self::FAILURE;
        }
        $apply = (bool) $this->option('apply');
        DB::transaction(function () use ($apply) {
            $changes = [];
            $backup = [];
            foreach (require database_path('data/public_content_corrections.php') as $table => $fields) {
                $query = DB::table($table)->orderBy('id');
                $rows = ($apply ? $query->lockForUpdate() : $query)->get();
                foreach ($rows as $row) {
                    foreach ($fields as $field => $rule) {
                        $original = (string) ($row->{$field} ?? '');
                        $fingerprint = hash('sha256', $original);
                        if (isset($rule['replacements'])) {
                            $rule = $rule['replacements'][$fingerprint] ?? null;
                            if (! $rule) {
                                continue;
                            }
                        }
                        if (! hash_equals($rule['before'], $fingerprint)) {
                            if (! isset($rule['after']) || $original !== $rule['after']) {
                                $this->line("Preserved {$table}#{$row->id}.{$field}: does not match reviewed legacy copy.");
                            }

                            continue;
                        }
                        $value = $rule['after'] ?? $this->privacy($original);
                        $changes[$table][$row->id][$field] = $value;
                        $backup[$table][$row->id] = (array) $row;
                        $this->line(($apply ? 'Updating ' : 'Would update ')."{$table}#{$row->id}.{$field}: ".Str::limit(strip_tags($value), 180));
                    }
                }
            }
            if (! $changes) {
                $this->info('No matching legacy content needs correction.');

                return;
            }
            if (! $apply) {
                $this->info('Dry run only. Use --apply to back up and write these changes.');

                return;
            }
            $directory = storage_path('app/private/public-content-backups');
            if (! is_dir($directory) && ! mkdir($directory, 0700, true)) {
                throw new \RuntimeException('Could not create the private backup directory. No content was changed.');
            }
            $path = $directory.'/'.now()->format('Ymd-His').'-'.Str::uuid().'.json';
            $handle = fopen($path, 'x');
            if (! $handle) {
                throw new \RuntimeException('Could not create the content backup. No content was changed.');
            }
            try {
                chmod($path, 0600);
                $json = json_encode($backup, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
                if (fwrite($handle, $json) !== strlen($json) || ! fflush($handle)) {
                    throw new \RuntimeException('Could not finish the content backup. No content was changed.');
                }
            } finally {
                fclose($handle);
            }
            foreach ($changes as $table => $rows) {
                foreach ($rows as $id => $values) {
                    DB::table($table)->where('id', $id)->update([...$values, 'updated_at' => now()]);
                }
            }
            $this->info('Corrections saved. Private backup: '.$path);
        });

        return self::SUCCESS;
    }

    private function privacy(string $html): string
    {
        $html = str_replace(
            'iTechOrbit is a technology-focused platform providing programming tutorials, technical articles, software development resources, and information about web design, web development, and related IT services.',
            'iTechOrbit provides software engineering services, website products and technical articles. Personal portfolios describe the work and experience of their individual owners.',
            $html
        );
        $html = preg_replace('/Last Updated: October 17, 2026/', 'Last Updated: '.now()->format('F j, Y'), $html, 1);

        return $html.'<h2>Public form spam protection</h2><p>Where enabled, public forms use Google reCAPTCHA to help detect automated submissions. Google processes browser and device information for this verification. Our server sends the verification token to Google and checks the result before accepting a protected submission. See Google’s <a href="https://policies.google.com/privacy">Privacy Policy</a> and <a href="https://policies.google.com/terms">Terms of Service</a>.</p>';
    }
}
