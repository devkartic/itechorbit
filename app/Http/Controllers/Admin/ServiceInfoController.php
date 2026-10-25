<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\WebsiteServiceRequest;
use App\Models\Admin\ServiceInfo;
use App\Models\Admin\WebsiteTechnology;
use App\Services\Admin\AdminPresentation;
use App\Services\Shared\MediaFiles;
use Illuminate\Http\Response;

class ServiceInfoController extends Controller
{
    /**
     * Check permission for routs, roles, links.
     *
     * @return Response or exception
     */
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            PermissionController::permission_verify();

            return $next($request);
        });
    }

    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index()
    {
        return app(AdminPresentation::class)->render('service-info', 'index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function create()
    {
        return app(AdminPresentation::class)->render('service-info', 'create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return Response
     */
    public function store(WebsiteServiceRequest $request)
    {
        $this->saveService($request, new ServiceInfo);

        return response()->json(['success' => 'Service created successfully']);
    }

    /**
     * Display the specified resource.
     *
     * @return Response
     */
    public function show(ServiceInfo $serviceInfo)
    {
        return app(AdminPresentation::class)->render('service-info', 'show', compact('serviceInfo'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @return Response
     */
    public function edit(ServiceInfo $serviceInfo)
    {
        return app(AdminPresentation::class)->render('service-info', 'edit', compact('serviceInfo'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @return Response
     */
    public function update(WebsiteServiceRequest $request, ServiceInfo $serviceInfo)
    {
        $this->saveService($request, $serviceInfo);

        return response()->json(['success' => 'Service updated successfully']);
    }

    private function saveService(WebsiteServiceRequest $request, ServiceInfo $service): void
    {
        $data = $request->safe()->except(['uploaded_image', 'is_checked', 'remove_image', 'technology_ids']);
        $data['status'] = $request->boolean('is_checked');
        $data['highlight'] = $request->boolean('highlight');
        $data['display_order'] = $data['display_order'] ?? 0;
        $data['icon'] = $data['icon'] ?? 'code';
        if ($request->boolean('remove_image')) {
            $data['image_path'] = null;
        }
        MediaFiles::save($service, $data, $request, [
            'uploaded_image' => ['column' => 'image_path', 'directory' => 'uploads/website-services'],
        ], function () use ($service, $request) {
            $ids = $request->validated('technology_ids', []);
            WebsiteTechnology::whereIn('id', $ids)->orderBy('id')->lockForUpdate()->get();
            $service->websiteTechnologies()->sync($ids);
        });
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return Response
     */
    public function destroy(ServiceInfo $serviceInfo)
    {
        MediaFiles::destroy($serviceInfo, ['image_path']);

        return response()->json(['success' => 'Service deleted successfully']);
    }
}
