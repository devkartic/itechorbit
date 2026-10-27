<?php

namespace App\Http\Controllers\Shared;

use App\Http\Controllers\Controller;
use App\Http\Requests\ContactRequest;
use App\Jobs\ContactMailerJob;
use App\Models\Admin\WebsitePage;
use App\Models\Contact;
use App\Models\User;
use App\Services\Shared\PublicContent;
use Illuminate\Support\Str;
use Inertia\Inertia;

abstract class PublicPageController extends Controller
{
    public function __construct(protected PublicContent $content) {}

    protected function page(string $component, string $title, string $description, array $props = [], ?string $canonical = null, bool $noindex = false)
    {
        $key = ['Website/Home' => 'home', 'Website/Services' => 'services', 'Website/Products' => 'products', 'Website/Blog' => 'blogs', 'Website/Work' => 'work'][$component] ?? null;
        $record = $key ? WebsitePage::where('page_key', $key)->where('status', true)->first() : null;
        $pageContent = $record?->only(['heading', 'introduction', 'cta_label', 'cta_url']);
        // Categories retain their own metadata and heading rather than the collection defaults.
        $category = $component === 'Website/Blog' ? ($props['category'] ?? null) : null;
        if ($category) {
            $pageContent = [...($pageContent ?? []), 'heading' => $category['title'] ?: $category['name'],
                'introduction' => 'Technical articles and practical engineering notes about '.$category['name'].'.'];
        } else {
            $title = $record?->meta_title ?: $title;
            $description = $record?->meta_description ?: $description;
        }
        if (in_array($component, ['Website/Blog', 'Website/Work']) && (int) request('page', 1) > 1 && ! str_contains($title, ' — Page ')) {
            $title .= ' — Page '.(int) request('page');
        }
        $image = $record?->share_image ?? ($props['profile']['image'] ?? null);
        foreach (['article', 'project', 'service', 'product'] as $type) {
            $image ??= $props[$type]['image'] ?? null;
        }
        $base = rtrim(config('app.url'), '/');
        if ($image && str_starts_with($image, '/')) {
            $image = $base.$image;
        }
        $description = Str::limit(trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($description), ENT_QUOTES | ENT_HTML5, 'UTF-8'))), 180);

        return Inertia::render($component, [...$props, 'publicPage' => $pageContent, 'seo' => [
            'title' => $title.' | iTechOrbit', 'description' => $description,
            'canonical' => $base.($canonical ?? request()->getPathInfo()),
            'image' => $image, 'imageAlt' => $title,
            'noindex' => $noindex, 'type' => $component === 'Website/Article' ? 'article' : 'website',
        ]]);
    }

    protected function saveInquiry(ContactRequest $request, User $owner, bool $personal)
    {
        $contact = Contact::create([...$request->safe()->only(['name', 'email', 'subject', 'message']), 'user_id' => $owner->id]);
        ContactMailerJob::dispatch($owner, $contact, $personal)->afterCommit();

        return back()->with('success', $personal ? 'Thank you. Your message has been received for '.$owner->name.'.' : 'Thank you. Your inquiry has been received.');
    }
}
