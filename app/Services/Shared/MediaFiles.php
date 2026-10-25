<?php

namespace App\Services\Shared;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class MediaFiles
{
    /** Stage uploads together, then publish their references in one database transaction. */
    public static function save(Model $record, array $attributes, Request $request, array $bindings, ?callable $afterSave = null): void
    {
        $staged = [];
        $previous = [];
        $committed = false;
        try {
            foreach ($bindings as $field => $binding) {
                $column = $binding['column'];
                $previous[$column] = $record->getRawOriginal($column);
                if ($file = $request->file($field)) {
                    $path = $binding['directory'].'/'.Str::uuid().'.'.$file->extension();
                    // Include partial writes in cleanup if the filesystem operation fails.
                    $staged[] = $path;
                    try {
                        if (! Storage::disk('public')->putFileAs($binding['directory'], $file, basename($path))) {
                            throw new \RuntimeException('The upload could not be written to persistent storage.');
                        }
                    } catch (\Throwable $exception) {
                        report($exception);
                        throw ValidationException::withMessages([$field => 'The file could not be saved. Your previous files have been kept. Please try again.']);
                    }
                    $attributes[$column] = $path;
                }
            }
            DB::transaction(function () use ($record, $attributes, $previous, &$committed, $afterSave) {
                // Mark commit before model observers dispatch their after-commit jobs.
                DB::afterCommit(function () use (&$committed) {
                    $committed = true;
                });
                if (! $record->fill($attributes)->save()) {
                    throw new \RuntimeException('The record could not be saved.');
                }
                if ($afterSave) {
                    $afterSave($record);
                }
                $obsolete = [];
                foreach ($previous as $column => $path) {
                    if ($path && $path !== $record->{$column}) {
                        $obsolete[] = $path;
                    }
                }
                DB::afterCommit(fn () => self::cleanup($obsolete));
            });
        } catch (\Throwable $exception) {
            if (! $committed) {
                self::cleanup($staged);
            }
            throw $exception;
        }
    }

    public static function destroy(Model $record, array $columns): void
    {
        $paths = array_map(fn ($column) => $record->{$column}, $columns);
        DB::transaction(function () use ($record, $paths) {
            if (! $record->delete()) {
                throw new \RuntimeException('The record could not be deleted.');
            }
            DB::afterCommit(fn () => self::cleanup($paths));
        });
    }

    private static function cleanup(array $paths): void
    {
        foreach (array_filter($paths) as $path) {
            try {
                self::delete($path);
            } catch (\Throwable $exception) {
                // A cleanup failure must never roll back a committed reference or delete its replacement.
                Log::warning('An obsolete upload could not be removed.', ['path' => $path, 'exception' => $exception]);
            }
        }
    }

    public static function resolve(?string $path): ?string
    {
        if (! $path || ! str_starts_with($path, 'uploads/') || str_contains($path, '..')) {
            return null;
        }
        foreach (self::locations($path) as [$root, $file]) {
            if ($root && $file && str_starts_with($file, $root.DIRECTORY_SEPARATOR) && is_file($file)) {
                return $file;
            }
        }

        return null;
    }

    /** New files use Laravel's public disk; old /uploads URLs remain readable. */
    public static function url(?string $path): ?string
    {
        $file = self::resolve($path);
        if (! $file) {
            return null;
        }

        return $file === realpath(Storage::disk('public')->path($path)) ? '/storage/'.$path : '/'.$path;
    }

    private static function locations(string $path): array
    {
        $disk = Storage::disk('public');

        return array_unique([
            [realpath($disk->path('uploads')), realpath($disk->path($path))],
            [realpath(public_path('uploads')), realpath(public_path($path))],
        ], SORT_REGULAR);
    }

    public static function delete(?string $path): void
    {
        if (! $path || ! str_starts_with($path, 'uploads/') || str_contains($path, '..')) {
            return;
        }
        // A preserved legacy file may exist in both locations. Remove both only
        // after its record transaction commits, using the same containment checks.
        foreach (self::locations($path) as [$root, $file]) {
            if ($root && $file && str_starts_with($file, $root.DIRECTORY_SEPARATOR) && is_file($file) && ! File::delete($file)) {
                throw new \RuntimeException('The obsolete upload could not be deleted.');
            }
        }
    }
}
