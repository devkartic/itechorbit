<?php

namespace Tests\Feature;

use App\Services\Shared\MediaFiles;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class PersistentMediaTest extends TestCase
{
    private string $publicDirectory;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->publicDirectory = sys_get_temp_dir().'/itechorbit-media-'.Str::uuid();
        File::makeDirectory($this->publicDirectory.'/uploads/profiles', 0755, true);
        $this->app->usePublicPath($this->publicDirectory);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->publicDirectory);
        parent::tearDown();
    }

    public function test_existing_uploads_are_copied_to_the_public_disk_without_changing_originals(): void
    {
        $legacy = public_path('uploads/profiles/photo.png');
        file_put_contents($legacy, 'original upload');
        $this->assertSame('/uploads/profiles/photo.png', MediaFiles::url('uploads/profiles/photo.png'));

        $this->artisan('media:preserve-uploads')->assertSuccessful();
        $disk = Storage::disk('public');
        $this->assertSame('original upload', $disk->get('uploads/profiles/photo.png'));
        $this->assertSame('original upload', file_get_contents($legacy));
        $this->assertSame('/storage/uploads/profiles/photo.png', MediaFiles::url('uploads/profiles/photo.png'));
        $this->assertSame(realpath($disk->path('uploads/profiles/photo.png')), MediaFiles::resolve('uploads/profiles/photo.png'));
        $this->artisan('media:preserve-uploads')->assertSuccessful();

        // Source replacement no longer makes the upload disappear.
        unlink($legacy);
        $this->assertNotNull(MediaFiles::resolve('uploads/profiles/photo.png'));
    }

    public function test_conflicting_copies_fail_without_overwriting_either_file(): void
    {
        file_put_contents(public_path('uploads/profiles/photo.png'), 'legacy content');
        Storage::disk('public')->put('uploads/profiles/photo.png', 'persistent content');
        $this->artisan('media:preserve-uploads')->assertFailed();
        $this->assertSame('legacy content', file_get_contents(public_path('uploads/profiles/photo.png')));
        $this->assertSame('persistent content', Storage::disk('public')->get('uploads/profiles/photo.png'));
    }

    public function test_the_standard_storage_link_serves_the_persistent_public_disk(): void
    {
        $disk = Storage::disk('public');
        $disk->put('uploads/photo.png', 'persistent image');
        config(['filesystems.links' => [public_path('storage') => $disk->path('')]]);
        $this->artisan('storage:link')->assertSuccessful();
        $this->assertTrue(is_link(public_path('storage')));
        $this->assertSame('persistent image', file_get_contents(public_path('storage/uploads/photo.png')));
    }

    public function test_deleting_an_attachment_removes_both_safe_copies(): void
    {
        file_put_contents(public_path('uploads/profiles/photo.png'), 'upload');
        $this->artisan('media:preserve-uploads')->assertSuccessful();
        MediaFiles::delete('uploads/profiles/photo.png');
        $this->assertFileDoesNotExist(public_path('uploads/profiles/photo.png'));
        Storage::disk('public')->assertMissing('uploads/profiles/photo.png');
    }

    public function test_persistent_uploads_cannot_resolve_or_delete_outside_their_directory(): void
    {
        $disk = Storage::disk('public');
        $disk->makeDirectory('uploads');
        $disk->put('private.txt', 'private file');
        symlink($disk->path('private.txt'), $disk->path('uploads/external.txt'));
        $this->assertNull(MediaFiles::resolve('uploads/external.txt'));
        $this->assertNull(MediaFiles::url('uploads/external.txt'));
        MediaFiles::delete('uploads/external.txt');
        $this->assertSame('private file', $disk->get('private.txt'));
    }
}
