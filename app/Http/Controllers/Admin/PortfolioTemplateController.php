<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\AdminPresentation;
use App\Services\Portfolio\PortfolioPresentation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class PortfolioTemplateController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'verified']);
        $this->middleware(function ($request, $next) {
            PermissionController::initial_verification($request->user());

            return $next($request);
        });
    }

    public function index(Request $request, PortfolioPresentation $portfolio)
    {
        return Inertia::render('Admin/PortfolioTemplates', [
            ...$portfolio->data($request->user()),
            'templates' => collect(PortfolioPresentation::TEMPLATES)->map(fn ($item, $id) => ['id' => $id, ...$item])->values(),
            'navigation' => app(AdminPresentation::class)->navigation(),
            'storageReady' => Schema::hasColumn('users', 'portfolio_template'),
            'publishedUrl' => $request->user()->portfolio_status && $request->user()->portfolio_id ? '/portfolios/'.rawurlencode($request->user()->portfolio_id) : null,
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate(['template' => ['required', 'string', Rule::in(array_keys(PortfolioPresentation::TEMPLATES))]]);
        if (! Schema::hasColumn('users', 'portfolio_template')) {
            throw ValidationException::withMessages(['template' => 'Template saving is unavailable until the portfolio database migration is applied. Please contact the site administrator.']);
        }
        $request->user()->forceFill(['portfolio_template' => $data['template']])->save();

        return to_route('workspace.portfolio-templates')->with('success', 'Portfolio template saved. This design will be used for your public portfolio.');
    }
}
