<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PreserveUploads extends Command
{
    protected $signature = 'media:preserve-uploads';

    protected $description = 'Copy legacy public/uploads files into persistent Laravel storage without deleting or replacing originals';

    public function handle(): int
    {
        $legacy = realpath(public_path('uploads'));
        $disk = Storage::disk('public');
        if (! $legacy || $legacy === realpath($disk->path('uploads'))) {
            $this->info('No legacy uploads need copying.');

            return self::SUCCESS;
        }

        $copied = 0;
        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($legacy, \FilesystemIterator::SKIP_DOTS));
        foreach ($files as $file) {
            if ($file->isLink() || ! $file->isFile()) {
                $this->error('A legacy upload is a symbolic link or unsupported file; originals have been kept.');

                return self::FAILURE;
            }
            $relative = 'uploads/'.str_replace(DIRECTORY_SEPARATOR, '/', substr($file->getPathname(), strlen($legacy) + 1));
            $checksum = hash_file('sha256', $file->getPathname());
            if ($checksum === false) {
                $this->error('A legacy upload could not be read: '.$relative.'. The original has been kept.');

                return self::FAILURE;
            }
            if ($disk->exists($relative)) {
                if ($checksum !== hash_file('sha256', $disk->path($relative))) {
                    $this->error('An upload conflicts with persistent storage: '.$relative.'. Neither file was replaced.');

                    return self::FAILURE;
                }

                continue;
            }
            $stream = fopen($file->getPathname(), 'rb');
            if ($stream === false) {
                $this->error('A legacy upload could not be opened: '.$relative.'. The original has been kept.');

                return self::FAILURE;
            }
            $temporary = 'uploads/.preserve-'.Str::uuid();
            try {
                $written = $disk->put($temporary, $stream);
            } finally {
                fclose($stream);
            }
            if (! $written || $checksum !== hash_file('sha256', $disk->path($temporary))) {
                $disk->delete($temporary);
                $this->error('An upload could not be copied and verified: '.$relative.'. The original has been kept.');

                return self::FAILURE;
            }
            if (! $disk->move($temporary, $relative)) {
                $disk->delete($temporary);
                $this->error('A verified upload could not be published: '.$relative.'. The original has been kept.');

                return self::FAILURE;
            }
            $copied++;
        }

        $this->info('Preserved '.$copied.' legacy uploads in Laravel storage. Originals were kept.');

        return self::SUCCESS;
    }
}
