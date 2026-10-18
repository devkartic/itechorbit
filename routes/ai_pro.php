<?php

use App\AiPro\Http\AdminController;
use App\AiPro\Models\Site;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

Route::get('/ai-pro/widget.js', fn () => response()->file(resource_path('ai-pro/widget.js'), ['Content-Type' => 'application/javascript', 'Cache-Control' => 'public, max-age=3600']));
Route::get('/ai-pro/local-config', function () {
    $site = config('ai_pro.enabled') && Schema::hasTable('ai_pro_sites')
        ? Site::where('public_id', config('ai_pro.local_site'))->where('is_local', true)->where('enabled', true)->first() : null;

    return response()->json(['site' => $site?->public_id, 'widget_version' => substr(hash_file('sha256', resource_path('ai-pro/widget.js')), 0, 12)])->header('Cache-Control', 'no-store');
});
Route::prefix('workspace/ai-pro')->controller(AdminController::class)->group(function () {
    Route::get('/', 'index')->name('workspace.ai-pro');
    Route::get('/data', 'data');
    foreach (AdminController::SECTIONS as $section) {
        Route::get('/'.$section, 'page')->defaults('section', $section)->name('workspace.ai-pro.'.$section);
    }
    Route::post('/sites', 'saveSite')->middleware('throttle:20,1');
    Route::put('/sites/{site}', 'saveSite')->whereNumber('site')->middleware('throttle:20,1');
    Route::post('/sites/{site}/sources', 'saveSource')->whereNumber('site')->middleware('throttle:20,1');
    Route::put('/sites/{site}/sources/{source}', 'saveSource')->whereNumber(['site', 'source'])->middleware('throttle:20,1');
    Route::patch('/sites/{site}/sources/{source}', 'toggleSource')->whereNumber(['site', 'source']);
    Route::delete('/sites/{site}/sources/{source}', 'deleteSource')->whereNumber(['site', 'source']);
    Route::post('/sites/{site}/sync', 'sync')->whereNumber('site')->middleware('throttle:5,1');
    Route::get('/sites/{site}/conversations/{conversation}', 'messages')->whereNumber(['site', 'conversation']);
});
