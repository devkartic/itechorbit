<?php

namespace App\Http\Controllers;

use App\Http\Requests\ContactRequest;
use App\Jobs\ContactMailerJob;
use App\Models\Admin\AboutInfo;
use App\Models\Admin\Category;
use App\Models\Admin\ContactInfo;
use App\Models\Admin\Post;
use App\Models\Admin\PrivacyPolicy;
use App\Models\Admin\Project;
use App\Models\Admin\ServiceInfo;
use App\Models\Comment;
use App\Models\Contact;
use App\Models\User;
use App\Services\PortfolioPresentation;
use App\Services\PublicContent;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PublicSiteController extends Controller
{
    public function __construct(private PublicContent $content) {}

    private function page(string $component, string $title, string $description, array $props = [], ?string $canonical = null, bool $noindex = false)
    {
        return Inertia::render($component, [...$props, 'seo' => [
            'title' => $title.' | iTechOrbit', 'description' => $description,
            'canonical' => rtrim(config('app.url'), '/').($canonical ?? request()->getPathInfo()),
            'noindex' => $noindex, 'type' => $component === 'Article' ? 'article' : 'website',
            'verification' => config('site.verification'),
        ]]);
    }

    private function projects()
    {
        return Project::where('status', 1)->whereHas('user', fn ($query) => $query->where('status', 1)->where('portfolio_status', 1));
    }

    private function services(): array
    {
        return collect(config('site.services'))->map(fn ($service, $slug) => [...$service, 'slug' => $slug, 'url' => '/services/'.$slug])->values()->all();
    }

    public function home(Request $request)
    {
        if ($request->has('page')) {
            $query = $request->validate(['page' => ['required', 'integer', 'min:1']]);

            return redirect('/blogs?'.http_build_query($query), 301);
        }

        return $this->page('Home', 'Software engineering for what comes next', config('site.description'), [
            'articles' => Post::public_posts()->with(['category', 'user'])->latest('id')->limit(3)->get()->map(fn ($post) => $this->content->article($post)),
            'projects' => $this->projects()->orderBy('order_number')->limit(3)->get()->map(fn ($project) => $this->content->project($project)),
            'services' => $this->services(),
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
        $page = $this->page('Blog', 'Engineering notes'.($posts->currentPage() > 1 ? ' — Page '.$posts->currentPage() : ''),
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

        return $this->page('Article', $post->title, $article['excerpt'], [
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

        return $this->page('Work', 'Selected work', 'Explore published projects, engineering contributions and the technologies behind them.', [
            'projects' => $projects, 'categories' => $this->projects()->whereNotNull('project_category')->distinct()->pluck('project_category'), 'category' => $filters['category'] ?? '',
        ], '/work'.($projects->currentPage() > 1 ? '?page='.$projects->currentPage() : ''));
    }

    public function project(string $id)
    {
        $project = $this->projects()->findOrFail($id);

        return $this->page('Project', $project->name, strip_tags($project->about_project), ['project' => $this->content->project($project, true)]);
    }

    public function portfolio(?string $key = null)
    {
        $user = $key ? User::where('portfolio_id', $key)->firstOrFail() : User::findOrFail(config('site.owner_id'));
        abort_unless($user->status && $user->portfolio_status, 403, 'This portfolio is not available.');

        return $this->page('Portfolio', $user->name.' — Engineering portfolio', 'Published engineering work and professional experience.', app(PortfolioPresentation::class)->data($user));
    }

    public function offerings()
    {
        $info = ServiceInfo::where('status', 1)->first();

        return $this->page('Services', 'Software engineering services', config('site.description'), ['services' => $this->services(), 'existingContent' => $this->content->html($info?->descriptions)]);
    }

    public function service(string $slug)
    {
        $service = collect($this->services())->firstWhere('slug', $slug);
        abort_unless($service, 404);

        return $this->page('Service', $service['title'], $service['summary'], ['service' => $service]);
    }

    public function about()
    {
        $about = AboutInfo::where('status', 1)->first();

        return $this->page('About', 'Engineering with clarity', config('site.description'), ['existingContent' => $this->content->html($about?->descriptions), 'subtitle' => $about?->subtitle]);
    }

    public function contact(Request $request)
    {
        $input = $request->validate(['portfolio_id' => ['nullable', 'string', 'max:100']]);
        $recipient = ! empty($input['portfolio_id']) ? User::where('portfolio_id', $input['portfolio_id'])->where('status', 1)->where('portfolio_status', 1)->firstOrFail() : null;
        $info = ContactInfo::where('status', 1)->first();

        return $this->page('Contact', 'Let’s build what comes next', 'Discuss a software project, a modernization challenge or an engineering opportunity.', ['existingContent' => $this->content->html($info?->descriptions), 'recipient' => $recipient?->only(['name', 'portfolio_id'])]);
    }

    public function inquiry(ContactRequest $request)
    {
        $owner = $request->filled('portfolio_id')
            ? User::where('portfolio_id', $request->validated('portfolio_id'))->where('status', 1)->where('portfolio_status', 1)->firstOrFail()
            : User::where('status', 1)->findOrFail(config('site.owner_id'));
        $contact = Contact::create([...$request->safe()->only(['name', 'email', 'subject', 'message']), 'user_id' => $owner->id]);
        ContactMailerJob::dispatch($owner, $contact)->afterCommit();

        return back()->with('success', 'Thank you. Your inquiry has been received.');
    }

    public function privacy()
    {
        $policy = PrivacyPolicy::where('status', 1)->first();

        return $this->page('Content', $policy?->title ?? 'Privacy policy', $policy?->subtitle ?? 'iTechOrbit privacy information.', [
            'title' => $policy?->title ?? 'Privacy policy', 'content' => $this->content->html($policy?->descriptions),
        ]);
    }
}
