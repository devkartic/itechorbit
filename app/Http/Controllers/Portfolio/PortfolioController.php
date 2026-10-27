<?php

namespace App\Http\Controllers\Portfolio;

use App\Http\Controllers\Shared\PublicPageController;
use App\Http\Requests\ContactRequest;
use App\Models\User;
use App\Services\Portfolio\PortfolioPresentation;

class PortfolioController extends PublicPageController
{
    public function portfolio(?string $key = null)
    {
        $user = $key ? User::publicPortfolio($key) : User::findOrFail(config('site.owner_id'))->ensurePortfolioAccessible();

        $data = app(PortfolioPresentation::class)->data($user);
        $designation = $data['profile']['designation'];
        $description = $data['profile']['introduction'] ?: $data['profile']['objective'] ?: 'Published work and professional experience shared by '.$user->name.'.';
        $canonical = $user->portfolio_id ? '/portfolios/'.rawurlencode($user->portfolio_id) : '/portfolios';

        return $this->page('Portfolio/Show', $user->name.' — '.($designation ?: 'Professional portfolio'), $description, $data, $canonical);
    }

    public function portfolioContact(string $key)
    {
        $owner = User::publicPortfolio($key);

        return $this->page('Portfolio/Contact', 'Contact '.$owner->name, 'Send a personal portfolio inquiry to '.$owner->name.'.', [
            'recipient' => $owner->only(['name', 'portfolio_id']),
            'template' => app(PortfolioPresentation::class)->template($owner),
        ], noindex: true);
    }

    public function portfolioInquiry(ContactRequest $request, string $key)
    {
        // The URL determines the recipient; submitted user IDs, emails and portfolio IDs cannot override it.
        return $this->saveInquiry($request, User::publicPortfolio($key), true);
    }
}
