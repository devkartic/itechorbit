<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\WebsiteServiceRequest;
use App\Models\Admin\ServiceInfo;
use App\Services\Admin\AdminPresentation;
use App\Services\Shared\MediaFiles;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

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
        $data = $request->safe()->except(['uploaded_image', 'is_checked', 'remove_image']);
        $data['status'] = $request->boolean('is_checked');
        $data['highlight'] = $request->boolean('highlight');
        $data['display_order'] = $data['display_order'] ?? 0;
        $data['icon'] = $data['icon'] ?? 'code';
        $previous = $service->image_path;
        $uploaded = null;
        if ($request->boolean('remove_image')) {
            $data['image_path'] = null;
        }
        try {
            if ($file = $request->file('uploaded_image')) {
                $uploaded = 'uploads/website-services/'.Str::uuid().'.'.$file->extension();
                $file->move(public_path('uploads/website-services'), basename($uploaded));
                $data['image_path'] = $uploaded;
            }
            DB::transaction(fn () => $service->fill($data)->save());
        } catch (\Throwable $exception) {
            MediaFiles::delete($uploaded);
            throw $exception;
        }
        if ($previous !== $service->image_path) {
            MediaFiles::delete($previous);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return Response
     */
    public function destroy(ServiceInfo $serviceInfo)
    {
        $image = $serviceInfo->image_path;
        $serviceInfo->delete();
        MediaFiles::delete($image);

        return response()->json(['success' => 'Service deleted successfully']);
    }
}
