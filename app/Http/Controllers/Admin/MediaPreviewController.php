<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\Link;
use App\Models\Admin\Permission;
use App\Models\User;
use App\Services\Shared\MediaFiles;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class MediaPreviewController extends Controller
{
    public function __invoke(Request $request, string $resource, int $record, string $field): BinaryFileResponse
    {
        PermissionController::initial_verification($request->user());
        $module = config('admin.'.$resource);
        abort_unless($module && collect($module['fields'])->contains('storage', $field), 404);
        $link = Link::where('url', $resource)->value('id');
        abort_unless(Permission::has_permission($link, 'index') || Permission::has_permission($link, 'edit'), 403);
        $item = $module['model']::findOrFail($record);
        User::verify_ownership($item->user_id);
        $path = MediaFiles::resolve($item->{$field});
        abort_unless($path, 404);
        $mime = mime_content_type($path);
        abort_unless(in_array($mime, ['image/jpeg', 'image/png', 'image/webp', 'application/pdf'], true), 404);

        return response()->file($path, ['Content-Type' => $mime, 'Cache-Control' => 'private, no-store', 'X-Robots-Tag' => 'noindex, nofollow'])->setContentDisposition('inline', basename($path));
    }
}
