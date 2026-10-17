<?php

namespace App\Http\Controllers\Portfolio;

use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Shared\PublicPageController;
use App\Http\Requests\ContactRequest;
use App\Models\Admin\Link;
use App\Models\Admin\Permission;
use App\Models\User;
use App\Services\Portfolio\PortfolioPresentation;

class PortfolioController extends PublicPageController
{
    public function portfolio(?string $key = null)
    {
        $user = $key ? User::where('portfolio_id', $key)->firstOrFail() : User::findOrFail(config('site.owner_id'));
        $adminPreview = ! ($user->status && $user->portfolio_status);
        if ($adminPreview) {
            abort_unless(User::can_view_all_user_portfolios(), 403, 'This portfolio is not available.');
            PermissionController::initial_verification(request()->user());
            abort_unless(Permission::has_permission(Link::where('url', 'users')->value('id'), 'index'), 403);
        }

        return $this->page('Portfolio/Show', $user->name.' — Engineering portfolio', 'Published engineering work and professional experience.', [
            ...app(PortfolioPresentation::class)->data($user), 'adminPreview' => $adminPreview,
        ], noindex: $adminPreview);
    }

    private function portfolioOwner(string $key): User
    {
        return User::where('portfolio_id', $key)->where('status', 1)->where('portfolio_status', 1)->firstOrFail();
    }

    public function portfolioContact(string $key)
    {
        $owner = $this->portfolioOwner($key);

        return $this->page('Portfolio/Contact', 'Contact '.$owner->name, 'Send a personal portfolio inquiry to '.$owner->name.'.', [
            'recipient' => $owner->only(['name', 'portfolio_id']),
            'template' => app(PortfolioPresentation::class)->template($owner),
        ], noindex: true);
    }

    public function portfolioInquiry(ContactRequest $request, string $key)
    {
        // The URL determines the recipient; submitted user IDs, emails and portfolio IDs cannot override it.
        return $this->saveInquiry($request, $this->portfolioOwner($key), true);
    }
}
