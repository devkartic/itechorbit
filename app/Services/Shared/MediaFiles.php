<?php

namespace App\Services\Shared;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
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
                        $file->move(public_path($binding['directory']), basename($path));
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
            if (! File::delete($file)) {
                throw new \RuntimeException('The obsolete upload could not be deleted.');
            }
        }
    }
}
