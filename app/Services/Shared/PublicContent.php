<?php

namespace App\Services\Shared;

use App\Models\Admin\Post;
use App\Models\Admin\Project;
use App\Models\Admin\ServiceInfo;
use App\Models\Admin\WebsiteProduct;
use App\Models\Admin\WebsiteTechnology;
use Illuminate\Support\Str;
use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

class PublicContent
{
    public function html(?string $html): string
    {
        $config = (new HtmlSanitizerConfig)->allowSafeElements()
            ->allowElement('span', ['data-font-size', 'data-text-font'])->withAttributeSanitizer(new TextFormattingSanitizer)
            ->allowElement('pre', ['class'])->allowElement('code', ['class'])
            ->allowElement('h2', ['id'])->allowElement('h3', ['id'])
            ->allowLinkSchemes(['http', 'https', 'mailto'])->allowRelativeLinks()
            ->allowRelativeMedias()->withMaxInputLength(1500000);

        return (new HtmlSanitizer($config))->sanitize($html ?? '');
    }

    public function service(ServiceInfo $service, bool $detail = true): array
    {
        $technologies = $service->websiteTechnologies->where('status', true)->sortBy(fn ($technology) => sprintf('%010d', $technology->display_order).$technology->name)->values();

        return [
            'slug' => $service->slug,
            'title' => $service->title, 'short' => $service->short_title ?: $service->title, 'summary' => $service->subtitle,
            'url' => '/services/'.$service->slug,
            'image' => $this->media($service->image_path), 'icon' => $service->icon, 'highlight' => $service->highlight,
            'deliverables' => $service->deliverables ?? [], 'technologies' => $technologies->pluck('name')->all(),
            'technologyIds' => $technologies->pluck('id')->all(),
            ...($detail ? ['technologyLinks' => $technologies->map(fn ($technology) => $this->technology($technology))->all()] : []),
            'useCases' => $service->use_cases,
            ...($detail ? ['description' => $this->html($service->descriptions),
                'problem' => $service->problem, 'solution' => $service->solution, 'approach' => $service->approach] : []),
        ];
    }

    public function technology(WebsiteTechnology $technology): array
    {
        return app(TechnologyCatalog::class)->serialize($technology);
    }

    public function product(WebsiteProduct $service): array
    {
        return [
            'slug' => $service->slug,
            'title' => $service->title, 'short' => $service->short_title ?: $service->title, 'summary' => $service->subtitle,
            'description' => $this->html($service->descriptions), 'url' => '/products/'.$service->slug,
            'image' => $this->media($service->image_path), 'icon' => $service->icon, 'highlight' => $service->highlight,
            'problem' => $service->problem, 'solution' => $service->solution,
            'deliverables' => $service->deliverables ?? [], 'technologies' => $service->technologies ?? [],
            'price' => $service->price, 'currency' => $service->currency, 'deliveryTime' => $service->delivery_time,
            'approach' => $service->approach, 'useCases' => $service->use_cases,
        ];
    }

    public function article(Post $post, bool $detail = false): array
    {
        $html = $this->html($post->body);
        $item = [
            'id' => $post->id, 'title' => $post->title, 'url' => '/blogs/'.$post->id,
            'excerpt' => Str::limit(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8')), 180),
            'image' => $this->media($post->image_path),
            'category' => $post->category?->only(['id', 'name']),
            'author' => ['name' => $post->user?->name ?? 'iTechOrbit'],
            'tags' => $this->tags($post->tags),
            'date' => $post->created_at?->toDateString(),
            'readingMinutes' => max(1, (int) ceil(str_word_count(strip_tags($html)) / 200)),
        ];
        if ($detail) {
            $toc = [];
            $index = 0;
            $html = preg_replace_callback('/<h([23])\b[^>]*>(.*?)<\/h\1>/si', function ($match) use (&$toc, &$index) {
                $id = 'section-'.(++$index);
                $toc[] = ['id' => $id, 'title' => html_entity_decode(strip_tags($match[2])), 'level' => (int) $match[1]];

                return '<h'.$match[1].' id="'.$id.'">'.$match[2].'</h'.$match[1].'>';
            }, $html);
            $item += ['body' => $html, 'codebase' => $this->html($post->codebase), 'toc' => $toc,
                'updatedAt' => $post->updated_at?->toIso8601String()];
        }

        return $item;
    }

    public function project(Project $project, bool $detail = false): array
    {
        $item = ['id' => $project->id, 'name' => $project->name,
            'category' => $project->project_category, 'role' => $project->played_role,
            'technologies' => $this->tags($project->technology_used),
            'image' => $this->media($project->image_path), 'url' => '/work/'.$project->id,
            'excerpt' => Str::limit(strip_tags($project->about_project), 180)];
        if ($detail) {
            $contributions = $project->jobs ?? '';
            $contributions = preg_match('/<\/?[a-z][^>]*>/i', $contributions) ? $contributions : nl2br(e($contributions));
            $item += ['description' => $this->html($project->about_project), 'contributions' => $this->html($contributions),
                'liveUrl' => $this->externalUrl($project->project_url)];
        }

        return $item;
    }

    public function tags(?string $json): array
    {
        $items = json_decode($json ?? '[]', true);

        return is_array($items) ? array_values(array_filter($items, 'is_string')) : [];
    }

    public function media(?string $path): ?string
    {
        return MediaFiles::resolve($path) ? '/'.$path : null;
    }

    public function externalUrl(?string $url): ?string
    {
        return $url && filter_var($url, FILTER_VALIDATE_URL) && in_array(parse_url($url, PHP_URL_SCHEME), ['http', 'https']) ? $url : null;
    }
}
