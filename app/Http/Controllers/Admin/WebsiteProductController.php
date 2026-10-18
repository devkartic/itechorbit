<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\WebsiteProductRequest;
use App\Models\Admin\WebsiteProduct;
use App\Services\Admin\AdminPresentation;
use App\Services\Shared\MediaFiles;
use Illuminate\Http\Response;

class WebsiteProductController extends Controller
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
        return app(AdminPresentation::class)->render('product-info', 'index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function create()
    {
        return app(AdminPresentation::class)->render('product-info', 'create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return Response
     */
    public function store(WebsiteProductRequest $request)
    {
        $this->saveProduct($request, new WebsiteProduct);

        return response()->json(['success' => 'Product created successfully']);
    }

    /**
     * Display the specified resource.
     *
     * @return Response
     */
    public function show(WebsiteProduct $productInfo)
    {
        return app(AdminPresentation::class)->render('product-info', 'show', compact('productInfo'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @return Response
     */
    public function edit(WebsiteProduct $productInfo)
    {
        return app(AdminPresentation::class)->render('product-info', 'edit', compact('productInfo'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @return Response
     */
    public function update(WebsiteProductRequest $request, WebsiteProduct $productInfo)
    {
        $this->saveProduct($request, $productInfo);

        return response()->json(['success' => 'Product updated successfully']);
    }

    private function saveProduct(WebsiteProductRequest $request, WebsiteProduct $service): void
    {
        $data = $request->safe()->except(['uploaded_image', 'is_checked', 'remove_image']);
        $data['status'] = $request->boolean('is_checked');
        $data['highlight'] = $request->boolean('highlight');
        $data['display_order'] = $data['display_order'] ?? 0;
        $data['icon'] = $data['icon'] ?? 'code';
        if ($request->boolean('remove_image')) {
            $data['image_path'] = null;
        }
        MediaFiles::save($service, $data, $request, [
            'uploaded_image' => ['column' => 'image_path', 'directory' => 'uploads/website-products'],
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return Response
     */
    public function destroy(WebsiteProduct $productInfo)
    {
        MediaFiles::destroy($productInfo, ['image_path']);

        return response()->json(['success' => 'Product deleted successfully']);
    }
}
