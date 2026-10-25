<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Shared\PublicPageController;
use App\Http\Requests\ContactRequest;
use App\Models\Admin\AboutInfo;
use App\Models\Admin\Category;
use App\Models\Admin\ContactInfo;
use App\Models\Admin\Post;
use App\Models\Admin\PrivacyPolicy;
use App\Models\Admin\Project;
use App\Models\Admin\ServiceInfo;
use App\Models\Admin\WebsiteProduct;
use App\Models\Admin\WebsiteTechnology;
use App\Models\Comment;
use App\Models\User;
use App\Services\Shared\TechnologyCatalog;
use Illuminate\Http\Request;

class SiteController extends PublicPageController
{
    private function projects()
    {
        return Project::where('status', 1)->whereHas('user', fn ($query) => $query->where('status', 1)->where('portfolio_status', 1));
    }

    private function services(): array
    {
        return ServiceInfo::published()->with('websiteTechnologies')->get()->map(fn ($service) => $this->content->service($service, false))->all();
    }

    public function home(Request $request)
    {
        if ($request->has('page')) {
            $query = $request->validate(['page' => ['required', 'integer', 'min:1']]);

            return redirect('/blogs?'.http_build_query($query), 301);
        }

        return $this->page('Website/Home', 'Software engineering for what comes next', config('site.description'), [
            'articles' => Post::public_posts()->with(['category', 'user'])->latest('id')->limit(3)->get()->map(fn ($post) => $this->content->article($post)),
            'projects' => $this->projects()->orderBy('order_number')->limit(3)->get()->map(fn ($project) => $this->content->project($project)),
            'services' => $this->services(),
            'toolkit' => app(TechnologyCatalog::class)->visible('homepage')->map(fn ($technology) => $this->content->technology($technology))->all(),
            'products' => WebsiteProduct::published()->get()->map(fn ($product) => $this->content->product($product, false))->all(),
        ]);
    }

    public function blog(Request $request, ?string $category_id = null)
    {
        $filters = $request->validate(['key' => ['nullable', 'string', 'max:200'], 'tag' => ['nullable', 'string', 'max:100'], 'page' => ['nullable', 'integer', 'min:1']]);
        $query = Post::public_posts()->with(['category', 'user']);
        $key = $filters['key'] ?? '';
        if ($key !== '') {
            $query->where(fn ($q) => $q->where('title', 'like', '%'.$key.'%')->orWhere('body', 'like', '%'.$key.'%'));
        }
        if ($category_id) {
            $query->where('category_id', Category::findOrFail($category_id)->id);
        }
        if (! empty($filters['tag'])) {
            $query->whereJsonContains('tags', $filters['tag']);
        }
        $posts = $query->latest('id')->paginate(12)->withQueryString();
        abort_if($posts->currentPage() > $posts->lastPage(), 404);
        $posts->through(fn ($post) => $this->content->article($post));
        $page = $this->page('Website/Blog', 'Engineering notes'.($posts->currentPage() > 1 ? ' — Page '.$posts->currentPage() : ''),
            'Technical articles on Laravel, PHP, APIs and practical software engineering.', [
                'articles' => $posts, 'filters' => ['key' => $key, 'tag' => $filters['tag'] ?? '', 'category' => $category_id],
                'categories' => Category::where('status', 1)->orderBy('order_number')->get(['id', 'name']),
            ], $request->getPathInfo().($posts->currentPage() > 1 ? '?page='.$posts->currentPage() : ''), $key !== '' || ! empty($filters['tag']));

        return $page;
    }

    public function article(string $id)
    {
        $post = Post::public_posts()->with(['category', 'user'])->findOrFail($id);
        $post->increment('view_times');
        $article = $this->content->article($post, true);
        $comments = Comment::with('user:id,name')->where('commentable_type', Post::class)->where('commentable_id', $post->id)->orderBy('id')->paginate(30);
        $comments->through(fn ($comment) => ['id' => $comment->id, 'parentId' => $comment->parent_id, 'comment' => $comment->comment,
            'author' => $comment->user?->name ?? 'Member', 'userId' => $comment->user_id, 'date' => $comment->created_at?->toDateString()]);

        return $this->page('Website/Article', $post->title, $article['excerpt'], [
            'article' => $article, 'comments' => $comments,
            'related' => Post::public_posts()->with(['category', 'user'])->where('category_id', $post->category_id)->where('id', '!=', $post->id)->latest('id')->limit(3)->get()->map(fn ($item) => $this->content->article($item)),
        ]);
    }

    public function work(Request $request)
    {
        $filters = $request->validate(['category' => ['nullable', 'string', 'max:100'], 'page' => ['nullable', 'integer', 'min:1']]);
        $projects = $this->projects()->when($filters['category'] ?? null, fn ($q, $category) => $q->where('project_category', $category))
            ->orderBy('order_number')->paginate(12)->withQueryString()->through(fn ($item) => $this->content->project($item));
        abort_if($projects->currentPage() > $projects->lastPage(), 404);

        return $this->page('Website/Work', 'Selected work', 'Explore published projects, engineering contributions and the technologies behind them.', [
            'projects' => $projects, 'categories' => $this->projects()->whereNotNull('project_category')->distinct()->pluck('project_category'), 'category' => $filters['category'] ?? '',
        ], '/work'.($projects->currentPage() > 1 ? '?page='.$projects->currentPage() : ''));
    }

    public function project(string $id)
    {
        $project = $this->projects()->findOrFail($id);

        return $this->page('Website/Project', $project->name, strip_tags($project->about_project), ['project' => $this->content->project($project, true)]);
    }

    public function offerings(Request $request)
    {
        $filters = $request->validate(['technology' => ['nullable', 'string', 'max:180'], 'group' => ['nullable', 'string', 'max:100']]);
        $catalog = app(TechnologyCatalog::class);
        $group = $filters['group'] ?? '';
        $slug = $filters['technology'] ?? '';
        $selected = $group !== '' ? $catalog->resolve($group, 'group') : ($slug !== '' ? $catalog->resolve($slug) : null);
        if ($group !== '' || $slug !== '') {
            abort_unless($selected?->status, 404);
            if ($group !== '' || $selected->slug !== $slug) {
                return redirect($catalog->serialize($selected)['url'], 301);
            }
        }
        $services = ServiceInfo::published()->with('websiteTechnologies')
            ->when($selected, fn ($query) => $query->whereHas('websiteTechnologies', fn ($q) => $q->where('website_technologies.id', $selected->id)))
            ->get()->map(fn ($service) => $this->content->service($service, false))->all();

        return $this->page('Website/Services', 'Website & software engineering services', 'Website speed optimization, AI-generated code review, technical SEO, analytics, security audits and custom software engineering. Explore our services and discuss your project.', [
            'services' => $services,
            'technologies' => $catalog->visible('filters')->map(fn ($technology) => $this->content->technology($technology))->all(),
            'selectedTechnology' => $selected ? $this->content->technology($selected) : null,
        ], '/service/info', $selected !== null);
    }

    public function service(string $slug)
    {
        $record = ServiceInfo::published()->with('websiteTechnologies')->where('slug', $slug)->first();
        if (! $record && WebsiteProduct::published()->where('slug', $slug)->exists()) {
            return redirect('/products/'.$slug, 301);
        }
        abort_unless($record, 404);
        $service = $this->content->service($record);

        return $this->page('Website/Service', $service['title'], $service['summary'], ['service' => $service]);
    }

    public function products()
    {
        return $this->page('Website/Products', 'Website products', 'Purpose-built products to help your website serve visitors and support your business.', [
            'products' => WebsiteProduct::published()->get()->map(fn ($product) => $this->content->product($product, false))->all(),
        ]);
    }

    public function product(string $slug)
    {
        $product = $this->content->product(WebsiteProduct::published()->where('slug', $slug)->firstOrFail());

        return $this->page('Website/Product', $product['title'], $product['summary'], ['product' => $product]);
    }

    public function about()
    {
        $about = AboutInfo::where('status', 1)->first();

        return $this->page('Website/About', 'Engineering with clarity', config('site.description'), ['existingContent' => $this->content->html($about?->descriptions), 'subtitle' => $about?->subtitle]);
    }

    public function contact(Request $request)
    {
        $input = $request->validate(['portfolio_id' => ['nullable', 'string', 'max:100']]);
        $recipient = ! empty($input['portfolio_id']) ? User::where('portfolio_id', $input['portfolio_id'])->where('status', 1)->where('portfolio_status', 1)->firstOrFail() : null;
        if ($recipient) {
            $query = $request->validate(['subject' => ['nullable', 'string', 'max:255']]);

            return redirect('/portfolios/'.rawurlencode($recipient->portfolio_id).'/contact'.(! empty($query['subject']) ? '?'.http_build_query(['subject' => $query['subject']]) : ''));
        }
        $info = ContactInfo::where('status', 1)->orderBy('id')->first();

        return $this->page('Website/Contact', 'Let’s build what comes next', 'Discuss a software project, a modernization challenge or an engineering opportunity.', ['contactDetails' => $info?->publicDetails() ?? [], 'contactTitle' => $info?->title ?? 'What are you working on?', 'contactSubtitle' => $info?->subtitle ?? 'Share your idea, challenge, or next opportunity.', 'existingContent' => $this->content->html($info?->descriptions), 'recipient' => $recipient?->only(['name', 'portfolio_id'])]);
    }

    public function inquiry(ContactRequest $request)
    {
        $owner = $request->filled('portfolio_id')
            ? User::where('portfolio_id', $request->validated('portfolio_id'))->where('status', 1)->where('portfolio_status', 1)->firstOrFail()
            : User::where('status', 1)->findOrFail(config('site.owner_id'));

        return $this->saveInquiry($request, $owner, $request->filled('portfolio_id'));
    }

    public function privacy()
    {
        $policy = PrivacyPolicy::where('status', 1)->first();

        return $this->page('Website/Content', $policy?->title ?? 'Privacy policy', $policy?->subtitle ?? 'iTechOrbit privacy information.', [
            'title' => $policy?->title ?? 'Privacy policy', 'content' => $this->content->html($policy?->descriptions),
        ]);
    }
}
