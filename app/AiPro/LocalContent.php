<?php

namespace App\AiPro;

use App\AiPro\Models\Site;
use App\Models\Admin\AboutInfo;
use App\Models\Admin\ContactInfo;
use App\Models\Admin\Post;
use App\Models\Admin\PrivacyPolicy;
use App\Models\Admin\Project;
use App\Models\Admin\ServiceInfo;
use App\Models\Admin\WebsiteProduct;
use App\Services\Shared\PublicContent;

class LocalContent
{
    public static function text(string $html): string
    {
        $html = preg_replace('#<(script|style|nav|header|footer)\b[^>]*>.*?</\1>#is', '', $html);
        $html = preg_replace('#</(?:p|div|h[1-6]|li|br|section|tr)>|<br\s*/?>#i', "\n", $html);

        return trim(preg_replace('/[ \t]+/u', ' ', html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8')) ?? '');
    }

    public static function checksum(array $item): string
    {
        return hash('sha256', $item['title']."\n".$item['url']."\n".$item['content']);
    }

    public function snapshot(Site $site): array
    {
        $items = [];
        $add = function ($key, $title, $path, $content) use (&$items, $site) {
            $items[$key] = ['title' => mb_substr($title, 0, 255), 'url' => rtrim($site->url, '/').$path, 'content' => self::text($content)];
        };
        $add('home', $site->name, '/', config('site.description'));
        $presentation = app(PublicContent::class);
        foreach (ServiceInfo::published()->with('websiteTechnologies')->get() as $record) {
            $fields = $presentation->service($record);
            $content = implode("\n", array_filter([$fields['summary'], $fields['description'], $fields['problem'], $fields['solution'],
                implode("\n", $fields['deliverables']), implode(', ', $fields['technologies']), implode("\n", $fields['approach'] ?? []),
                implode("\n", array_map(fn ($row) => $row['title'].': '.$row['description'], $fields['useCases'] ?? []))]));
            $add('service:'.$record->id, $record->title, $fields['url'], $content);
        }
        foreach (WebsiteProduct::published()->get() as $record) {
            $fields = $presentation->product($record);
            $content = implode("\n", array_filter([$fields['summary'], $fields['description'], $fields['problem'], $fields['solution'],
                implode("\n", $fields['deliverables']), implode(', ', $fields['technologies']), implode("\n", $fields['approach'] ?? []),
                implode("\n", array_map(fn ($row) => $row['title'].': '.$row['description'], $fields['useCases'] ?? [])),
                $fields['price'] !== null ? 'Price: '.$fields['price'].' '.$fields['currency'] : null,
                $fields['deliveryTime'] ? 'Estimated delivery: '.$fields['deliveryTime'] : null]));
            $add('product:'.$record->id, $record->title, $fields['url'], $content);
        }
        foreach (Post::public_posts()->get() as $post) {
            $add('post:'.$post->id, $post->title, '/blogs/'.$post->id, $presentation->html($post->body));
        }
        foreach (Project::where('status', 1)->whereHas('user', fn ($q) => $q->where('status', 1)->where('portfolio_status', 1))->get() as $project) {
            $add('project:'.$project->id, $project->name, '/work/'.$project->id, $project->about_project."\n".$project->jobs."\n".$project->technology_used);
        }
        foreach (['about' => [AboutInfo::class, '/about/info'], 'contact' => [ContactInfo::class, '/contact/info'], 'privacy' => [PrivacyPolicy::class, '/privacy-n-policy']] as $key => [$model, $path]) {
            $record = $model::where('status', 1)->first();
            if ($record) {
                $add($key, $record->title ?: ucfirst($key), $path, ($record->subtitle ?? '')."\n".$record->descriptions);
            }
        }

        return $items;
    }
}
