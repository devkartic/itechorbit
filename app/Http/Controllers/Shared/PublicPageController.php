<?php

namespace App\Http\Controllers\Shared;

use App\Http\Controllers\Controller;
use App\Http\Requests\ContactRequest;
use App\Jobs\ContactMailerJob;
use App\Models\Contact;
use App\Models\User;
use App\Services\Shared\PublicContent;
use Inertia\Inertia;

abstract class PublicPageController extends Controller
{
    public function __construct(protected PublicContent $content) {}

    protected function page(string $component, string $title, string $description, array $props = [], ?string $canonical = null, bool $noindex = false)
    {
        return Inertia::render($component, [...$props, 'seo' => [
            'title' => $title.' | iTechOrbit', 'description' => $description,
            'canonical' => rtrim(config('app.url'), '/').($canonical ?? request()->getPathInfo()),
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
