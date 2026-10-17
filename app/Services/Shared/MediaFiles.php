<?php

namespace App\Services\Shared;

use Illuminate\Support\Facades\File;

class MediaFiles
{
    public static function resolve(?string $path): ?string
    {
        if (! $path || ! str_starts_with($path, 'uploads/') || str_contains($path, '..')) {
            return null;
        }
        $root = realpath(public_path('uploads'));
        $file = realpath(public_path($path));

        return $root && $file && str_starts_with($file, $root.DIRECTORY_SEPARATOR) && is_file($file) ? $file : null;
    }

    public static function delete(?string $path): void
    {
        if (! $path || ! str_starts_with($path, 'uploads/') || str_contains($path, '..')) {
            return;
        }
        $root = realpath(public_path('uploads'));
        $file = realpath(public_path($path));
        if ($root && $file && str_starts_with($file, $root.DIRECTORY_SEPARATOR) && is_file($file)) {
            File::delete($file);
        }
    }
}
