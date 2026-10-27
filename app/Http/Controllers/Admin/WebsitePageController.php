<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\WebsitePage;
use App\Services\Admin\AdminPresentation;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class WebsitePageController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            PermissionController::permission_verify();

            return $next($request);
        });
    }

    public function index()
    {
        return app(AdminPresentation::class)->render('public-pages', 'index');
    }

    public function edit(WebsitePage $publicPage)
    {
        return app(AdminPresentation::class)->render('public-pages', 'edit', ['page' => $publicPage]);
    }

    public function show(WebsitePage $publicPage)
    {
        return app(AdminPresentation::class)->render('public-pages', 'show', ['page' => $publicPage]);
    }

    public function update(Request $request, WebsitePage $publicPage)
    {
        $values = $request->validate([
            // Page identity is fixed; editing copy cannot reassign routes.
            'heading' => ['required', 'string', 'max:255'],
            'introduction' => ['required', 'string', 'max:3000'],
            'cta_label' => ['nullable', 'string', 'max:100'],
            'cta_url' => ['nullable', 'string', 'max:2048', 'regex:#^/(?!/)[a-zA-Z0-9/?&=_%+,.~\\-]*$#'],
            'meta_title' => ['nullable', 'string', 'max:150'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'share_image' => ['nullable', 'url:http,https', 'max:2048'],
            'is_checked' => ['required', Rule::in([true, false, 0, 1, '0', '1', 'true', 'false'])],
        ]);
        unset($values['is_checked']);
        $publicPage->update([...$values, 'status' => $request->boolean('is_checked')]);

        return response()->json(['success' => 'Public page updated.']);
    }
}
