<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\ServiceInfo;
use App\Services\Admin\AdminPresentation;
use Illuminate\Http\Request;
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
    public function store(Request $request)
    {
        $this->create_validator($request);

        ServiceInfo::create([
            'title' => $request->input('title'),
            'subtitle' => $request->input('subtitle'),
            'descriptions' => $request->input('descriptions'),
            'status' => filter_var($request->input('is_checked'), FILTER_VALIDATE_BOOLEAN),
        ]);

        return response()->json(['success' => 'Service created successfully']);
    }

    private function create_validator($request)
    {
        $request->validate([
            'title' => ['required', 'string', 'max:100'],
            'subtitle' => ['required', 'string', 'max:255'],
            'descriptions' => ['required', 'string'],
        ]);
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
    public function update(Request $request, ServiceInfo $serviceInfo)
    {
        $this->update_validator($request);
        $serviceInfo->title = $request->input('title');
        $serviceInfo->subtitle = $request->input('subtitle');
        $serviceInfo->descriptions = $request->input('descriptions');
        $serviceInfo->status = filter_var($request->input('is_checked'), FILTER_VALIDATE_BOOLEAN);
        $serviceInfo->save();

        return response()->json(['success' => 'Service updated successfully']);
    }

    private function update_validator($request)
    {
        $request->validate([
            'title' => ['required', 'string', 'max:100'],
            'subtitle' => ['required', 'string', 'max:255'],
            'descriptions' => ['required', 'string'],
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return Response
     */
    public function destroy(ServiceInfo $serviceInfo)
    {
        $serviceInfo->delete();

        return response()->json(['success' => 'Service deleted successfully']);
    }
}
