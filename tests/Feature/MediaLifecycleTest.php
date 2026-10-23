<?php

namespace Tests\Feature;

use App\Models\Admin\WebsiteProduct;
use App\Services\Shared\MediaFiles;
use App\Services\Shared\PublicContent;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class MediaLifecycleTest extends TestCase
{
    // Real commits exercise afterCommit cleanup, unlike a test-wide transaction.

    private string $directory;

    protected function setUp(): void
    {
        parent::setUp();
        // Use a new disposable database, without rolling back consolidated migrations at teardown.
        $this->assertSame('sqlite', DB::connection()->getDriverName());
        $this->assertSame(':memory:', DB::connection()->getDatabaseName());
        $this->artisan('migrate')->assertExitCode(0);
        $this->directory = 'uploads/media-lifecycle-'.Str::uuid();
        mkdir(public_path($this->directory), 0755, true);
    }

    protected function tearDown(): void
    {
        WebsiteProduct::flushEventListeners();
        DB::table('website_products')->delete();
        foreach (glob(public_path($this->directory).'/*') as $path) {
            is_dir($path) ? rmdir($path) : unlink($path);
        }
        rmdir(public_path($this->directory));
        parent::tearDown();
    }

    private function product(): WebsiteProduct
    {
        file_put_contents(public_path($this->directory.'/old.png'), 'old attachment');

        return WebsiteProduct::create(['title' => 'Product', 'slug' => 'test-media', 'subtitle' => 'Summary',
            'descriptions' => 'Details', 'image_path' => $this->directory.'/old.png']);
    }

    private function request(): Request
    {
        return Request::create('/', 'POST', [], [], ['uploaded_image' => UploadedFile::fake()->image('new.png')]);
    }

    private function bindings(): array
    {
        return ['uploaded_image' => ['column' => 'image_path', 'directory' => $this->directory]];
    }

    public function test_database_failure_preserves_old_file_and_removes_new_upload(): void
    {
        $record = $this->product();
        WebsiteProduct::saving(fn () => throw new \RuntimeException('Synthetic database failure'));
        try {
            MediaFiles::save($record, ['title' => 'Changed'], $this->request(), $this->bindings());
            $this->fail('Expected save failure.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Synthetic database failure', $exception->getMessage());
        }
        $this->assertSame('Product', $record->fresh()->title);
        $this->assertSame($this->directory.'/old.png', $record->fresh()->image_path);
        $this->assertSame(['old.png'], array_map('basename', glob(public_path($this->directory).'/*')));
    }

    public function test_failed_creation_does_not_leave_an_orphan_upload(): void
    {
        WebsiteProduct::saving(fn () => throw new \RuntimeException('Synthetic creation failure'));
        try {
            MediaFiles::save(new WebsiteProduct, ['title' => 'Product', 'slug' => 'failed-create', 'subtitle' => 'Summary', 'descriptions' => 'Details'], $this->request(), $this->bindings());
            $this->fail('Expected creation failure.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Synthetic creation failure', $exception->getMessage());
        }
        $this->assertDatabaseMissing('website_products', ['slug' => 'failed-create']);
        $this->assertSame([], glob(public_path($this->directory).'/*'));
    }

    public function test_successful_multiple_uploads_are_saved_together(): void
    {
        $record = $this->product();
        $request = $this->request();
        $request->files->set('second', UploadedFile::fake()->image('second.png'));
        MediaFiles::save($record, [], $request, [...$this->bindings(),
            'second' => ['column' => 'short_title', 'directory' => $this->directory]]);
        $this->assertFileExists(public_path($record->fresh()->image_path));
        $this->assertFileExists(public_path($record->fresh()->short_title));
        $this->assertFileDoesNotExist(public_path($this->directory.'/old.png'));
        $this->assertCount(2, glob(public_path($this->directory).'/*'));
    }

    public function test_second_upload_failure_cleans_staged_files_and_preserves_record(): void
    {
        $record = $this->product();
        file_put_contents(public_path($this->directory.'/blocked'), 'not a directory');
        $request = $this->request();
        $request->files->set('second', UploadedFile::fake()->image('second.png'));
        try {
            MediaFiles::save($record, ['title' => 'Changed'], $request, [...$this->bindings(),
                'second' => ['column' => 'short_title', 'directory' => $this->directory.'/blocked']]);
            $this->fail('Expected upload failure.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('second', $exception->errors());
        }
        $this->assertSame('Product', $record->fresh()->title);
        $this->assertFileExists(public_path($record->fresh()->image_path));
        $this->assertCount(2, glob(public_path($this->directory).'/*'));
    }

    public function test_replacement_removal_and_record_deletion_cleanup_after_commit(): void
    {
        $record = $this->product();
        $old = $record->image_path;
        MediaFiles::save($record, [], $this->request(), $this->bindings());
        $this->assertFileDoesNotExist(public_path($old));
        $this->assertFileExists(public_path($record->fresh()->image_path));
        $replacement = $record->image_path;
        MediaFiles::save($record, ['image_path' => null], new Request, $this->bindings());
        $this->assertNull($record->fresh()->image_path);
        $this->assertFileDoesNotExist(public_path($replacement));
        MediaFiles::save($record, [], $this->request(), $this->bindings());
        $replacement = $record->image_path;
        MediaFiles::destroy($record, ['image_path']);
        $this->assertNull($record->fresh());
        $this->assertFileDoesNotExist(public_path($replacement));
    }

    public function test_failed_removal_or_deletion_does_not_remove_existing_attachment(): void
    {
        $record = $this->product();
        WebsiteProduct::saving(fn () => false);
        try {
            MediaFiles::save($record, ['image_path' => null], new Request, $this->bindings());
            $this->fail('Expected cancelled save.');
        } catch (\RuntimeException) {
            $this->assertNotNull($record->fresh()->image_path);
            $this->assertFileExists(public_path($record->fresh()->image_path));
        }
        WebsiteProduct::deleting(fn () => false);
        try {
            MediaFiles::destroy($record->fresh(), ['image_path']);
            $this->fail('Expected cancelled deletion.');
        } catch (\RuntimeException) {
            $this->assertNotNull($record->fresh());
            $this->assertFileExists(public_path($record->fresh()->image_path));
        }
    }

    public function test_original_file_survives_until_outer_transaction_commits(): void
    {
        $record = $this->product();
        $old = $record->image_path;
        DB::transaction(function () use ($record, $old) {
            MediaFiles::save($record, [], $this->request(), $this->bindings());
            $this->assertFileExists(public_path($old));
        });
        $this->assertFileDoesNotExist(public_path($old));
    }

    public function test_public_and_admin_resolution_reject_missing_traversal_and_external_symlinks(): void
    {
        $outside = tempnam(sys_get_temp_dir(), 'media-outside-');
        file_put_contents($outside, 'private');
        symlink($outside, public_path($this->directory.'/external.png'));
        try {
            foreach (['../composer.json', 'uploads/../composer.json', $this->directory.'/missing.png', $this->directory.'/external.png'] as $path) {
                $this->assertNull(MediaFiles::resolve($path));
                $this->assertNull(app(PublicContent::class)->media($path));
                MediaFiles::delete($path);
            }
            $this->assertFileExists($outside);
        } finally {
            unlink($outside);
        }
    }
}
