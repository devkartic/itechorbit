<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\WebsiteTechnologyRequest;
use App\Models\Admin\WebsiteTechnology;
use App\Services\Admin\AdminPresentation;
use App\Services\Shared\TechnologyCatalog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class WebsiteTechnologyController extends Controller
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
        return app(AdminPresentation::class)->render('website-technologies', 'index');
    }

    public function create()
    {
        return app(AdminPresentation::class)->render('website-technologies', 'create');
    }

    public function show(WebsiteTechnology $websiteTechnology)
    {
        return app(AdminPresentation::class)->render('website-technologies', 'show', ['record' => $websiteTechnology]);
    }

    public function edit(WebsiteTechnology $websiteTechnology)
    {
        return app(AdminPresentation::class)->render('website-technologies', 'edit', ['record' => $websiteTechnology]);
    }

    public function store(WebsiteTechnologyRequest $request)
    {
        WebsiteTechnology::create($this->attributes($request));

        return response()->json(['success' => 'Technology created successfully']);
    }

    public function update(WebsiteTechnologyRequest $request, WebsiteTechnology $websiteTechnology)
    {
        app(TechnologyCatalog::class)->update($websiteTechnology, $this->attributes($request));

        return response()->json(['success' => 'Technology updated successfully']);
    }

    private function attributes(WebsiteTechnologyRequest $request): array
    {
        return [...$request->safe()->only(['name', 'slug']), 'display_order' => $request->input('display_order') ?? 0,
            'status' => $request->boolean('is_checked'), 'show_on_homepage' => $request->boolean('show_on_homepage'),
            ...($request->has('show_in_filters') ? ['show_in_filters' => $request->boolean('show_in_filters')] : [])];
    }

    public function merge(Request $request, WebsiteTechnology $websiteTechnology)
    {
        abort_unless(\App\Models\Admin\Permission::has_permission(\App\Models\Admin\Link::where('url', 'website-technologies')->value('id'), 'destroy'), 403);
        $input = $request->validate(['target_id' => ['required', 'integer', 'exists:website_technologies,id']]);
        app(TechnologyCatalog::class)->merge($websiteTechnology, WebsiteTechnology::findOrFail($input['target_id']));

        return response()->json(['success' => 'Technology merged. Service assignments and previous links were preserved.']);
    }

    public function destroy(WebsiteTechnology $websiteTechnology)
    {
        DB::transaction(function () use ($websiteTechnology) {
            $record = WebsiteTechnology::whereKey($websiteTechnology->id)->lockForUpdate()->firstOrFail();
            if ($record->services()->exists()) {
                throw ValidationException::withMessages(['technology' => 'Remove this technology from its services before deleting it. You can deactivate it to hide it publicly.']);
            }
            $record->delete();
        });

        return response()->json(['success' => 'Technology deleted successfully']);
    }
}
