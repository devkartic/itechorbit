<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\About;
use App\Models\Admin\AboutInfo;
use App\Services\AdminPresentation;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class AboutInfoController extends Controller
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
        return app(AdminPresentation::class)->render('about-info', 'index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function create()
    {
        return app(AdminPresentation::class)->render('about-info', 'create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return Response
     */
    public function store(Request $request)
    {
        $this->create_validator($request);

        AboutInfo::create([
            'title' => $request->input('title'),
            'subtitle' => $request->input('subtitle'),
            'descriptions' => $request->input('descriptions'),
            'status' => filter_var($request->input('is_checked'), FILTER_VALIDATE_BOOLEAN),
        ]);

        return response()->json(['success' => 'About created successfully']);
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
     * @param  About  $about
     * @return Response
     */
    public function show(AboutInfo $aboutInfo)
    {
        return app(AdminPresentation::class)->render('about-info', 'show', compact('aboutInfo'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  About  $about
     * @return Response
     */
    public function edit(AboutInfo $aboutInfo)
    {
        return app(AdminPresentation::class)->render('about-info', 'edit', compact('aboutInfo'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  About  $about
     * @return Response
     */
    public function update(Request $request, AboutInfo $aboutInfo)
    {
        $this->update_validator($request);
        $aboutInfo->title = $request->input('title');
        $aboutInfo->subtitle = $request->input('subtitle');
        $aboutInfo->descriptions = $request->input('descriptions');
        $aboutInfo->status = filter_var($request->input('is_checked'), FILTER_VALIDATE_BOOLEAN);
        $aboutInfo->save();

        return response()->json(['success' => 'About updated successfully']);
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
     * @param  About  $about
     * @return Response
     */
    public function destroy(AboutInfo $aboutInfo)
    {
        $aboutInfo->delete();

        return response()->json(['success' => 'About deleted successfully']);
    }
}
