<?php

namespace App\Services;

use Illuminate\Support\Facades\File;

class MediaFiles
{
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
