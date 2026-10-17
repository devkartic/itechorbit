<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\Service;
use App\Models\User;
use App\Services\Admin\AdminPresentation;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class ServiceController extends Controller
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
        return app(AdminPresentation::class)->render('services', 'index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function create()
    {
        return app(AdminPresentation::class)->render('services', 'create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return Response
     */
    public function store(Request $request)
    {
        $user_id = Auth::user()->id;
        $this->create_validator($request, $user_id);

        Service::create([
            'user_id' => $user_id,
            'name' => $request->input('name'),
            'fa_icon' => $request->input('fa_icon'),
            'service_url' => $request->input('service_url'),
            'order_number' => $request->input('order_number'),
            'title' => $request->input('title'),
            'about_service' => $request->input('about_service'),
            'status' => filter_var($request->input('is_checked'), FILTER_VALIDATE_BOOLEAN),
        ]);

        return response()->json(['success' => 'Service created successfully']);
    }

    private function create_validator($request, $user_id)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('services')
                ->where('name', $request->input('name'))
                ->where('user_id', $user_id)],
            'title' => ['required', 'string', 'max:255'],
            'about_service' => ['required', 'string', 'max:255'],
            'order_number' => ['integer', 'nullable', 'max:100'],
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @return Response
     */
    public function show(Service $service)
    {
        User::verify_ownership($service->user_id);

        return app(AdminPresentation::class)->render('services', 'show', compact('service'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @return Response
     */
    public function edit(Service $service)
    {
        User::verify_ownership($service->user_id);

        return app(AdminPresentation::class)->render('services', 'edit', compact('service'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @return Response
     */
    public function update(Request $request, Service $service)
    {
        User::verify_ownership($service->user_id);

        $user_id = Auth::user()->id;
        $this->update_validator($request, $service, $user_id);

        $service->name = $request->input('name');
        $service->fa_icon = $request->input('fa_icon');
        $service->service_url = $request->input('service_url');
        $service->order_number = $request->input('order_number');
        $service->title = $request->input('title');
        $service->about_service = $request->input('about_service');
        $service->status = filter_var($request->input('is_checked'), FILTER_VALIDATE_BOOLEAN);
        $service->save();

        return response()->json(['success' => 'Services updated successfully']);
    }

    private function update_validator($request, $service, $user_id)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('services')
                ->where('name', $request->input('name'))
                ->where('user_id', $user_id)
                ->whereNot('id', $service->id)],
            'title' => ['required', 'string', 'max:255'],
            'about_service' => ['required', 'string', 'max:255'],
            'order_number' => ['integer', 'nullable', 'max:100'],
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return Response
     */
    public function destroy(Service $service)
    {
        User::verify_ownership($service->user_id);

        $service->delete();

        return response()->json(['success' => 'Services deleted successfully']);
    }
}
