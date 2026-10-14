<?php

namespace App\Http\Controllers;

use App\Models\Admin\Category;
use App\Models\Admin\Post;
use App\Models\Admin\Project;
use App\Models\User;

class SitemapController extends Controller
{
    private function projects()
    {
        return Project::where('status', 1)->whereHas('user', fn ($q) => $q->where('status', 1)->where('portfolio_status', 1));
    }

    public function index()
    {
        $base = rtrim(config('app.url'), '/');
        $xml = '<?xml version="1.0" encoding="UTF-8"?><sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
        $xml .= '<sitemap><loc>'.e($base.'/sitemaps/pages.xml').'</loc></sitemap>';
        foreach (['articles' => Post::public_posts()->count(), 'projects' => $this->projects()->count(), 'portfolios' => User::where('status', 1)->where('portfolio_status', 1)->whereNotNull('portfolio_id')->count()] as $type => $count) {
            for ($page = 1; $page <= (int) ceil($count / 10000); $page++) {
                $xml .= '<sitemap><loc>'.e($base.'/sitemaps/'.$type.'-'.$page.'.xml').'</loc></sitemap>';
            }
        }

        return response($xml.'</sitemapindex>')->header('Content-Type', 'application/xml; charset=UTF-8');
    }

    public function pages()
    {
        $urls = ['/', '/blogs', '/work', '/about/info', '/service/info', '/contact/info', '/privacy-n-policy'];
        foreach (array_keys(config('site.services')) as $slug) {
            $urls[] = '/services/'.$slug;
        }
        foreach (Category::where('status', 1)->whereHas('posts', fn ($q) => $q->where('status', 1)->where('is_private', 0))->pluck('id') as $id) {
            $urls[] = '/blogs/category/'.$id;
        }
        $owner = User::find(config('site.owner_id'));
        if ($owner?->status && $owner?->portfolio_status) {
            $urls[] = '/portfolios';
        }

        return $this->xml(collect($urls)->map(fn ($url) => ['url' => $url]));
    }

    public function records(string $type, int $page)
    {
        abort_unless(in_array($type, ['articles', 'projects', 'portfolios']) && $page > 0, 404);
        $query = match ($type) {
            'articles' => Post::public_posts(), 'projects' => $this->projects(), 'portfolios' => User::where('status', 1)->where('portfolio_status', 1)->whereNotNull('portfolio_id')
        };
        abort_if(($page - 1) * 10000 >= $query->count(), 404);
        $records = $query->orderBy('id')->offset(($page - 1) * 10000)->limit(10000)->get($type === 'portfolios' ? ['id', 'portfolio_id', 'updated_at'] : ['id', 'updated_at']);

        return $this->xml($records->map(fn ($item) => ['url' => match ($type) {
            'articles' => '/blogs/'.$item->id, 'projects' => '/work/'.$item->id, 'portfolios' => '/portfolios/'.$item->portfolio_id
        }, 'modified' => $item->updated_at?->toIso8601String()]));
    }

    private function xml($records)
    {
        $base = rtrim(config('app.url'), '/');
        $xml = '<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
        foreach ($records as $record) {
            $xml .= '<url><loc>'.e($base.$record['url']).'</loc>'.(! empty($record['modified']) ? '<lastmod>'.e($record['modified']).'</lastmod>' : '').'</url>';
        }

        return response($xml.'</urlset>')->header('Content-Type', 'application/xml; charset=UTF-8');
    }

    public function robots()
    {
        $paths = array_keys(config('admin'));
        $text = "User-agent: *\n";
        foreach ($paths as $path) {
            $text .= 'Disallow: /'.$path."\n";
        }
        // /services/{slug} is public; allow those pages even though /services is a legacy admin endpoint.
        foreach (array_keys(config('site.services')) as $slug) {
            $text .= 'Allow: /services/'.$slug."\n";
        }
        $text .= "Disallow: /home\nDisallow: /account/\nDisallow: /permissions\nDisallow: /login\nDisallow: /register\nDisallow: /password/\nDisallow: /email/\nDisallow: /blogs/search\nDisallow: /*?key=\nDisallow: /get-\nDisallow: /healthz\nSitemap: ".rtrim(config('app.url'), '/')."/sitemap.xml\n";

        return response($text)->header('Content-Type', 'text/plain; charset=UTF-8');
    }
}
