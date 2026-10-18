<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\WebsiteProductRequest;
use App\Models\Admin\WebsiteProduct;
use App\Services\Admin\AdminPresentation;
use App\Services\Shared\MediaFiles;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

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
        $previous = $service->image_path;
        $uploaded = null;
        if ($request->boolean('remove_image')) {
            $data['image_path'] = null;
        }
        try {
            if ($file = $request->file('uploaded_image')) {
                $uploaded = 'uploads/website-products/'.Str::uuid().'.'.$file->extension();
                $file->move(public_path('uploads/website-products'), basename($uploaded));
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
    public function destroy(WebsiteProduct $productInfo)
    {
        $image = $productInfo->image_path;
        $productInfo->delete();
        MediaFiles::delete($image);

        return response()->json(['success' => 'Product deleted successfully']);
    }
}
