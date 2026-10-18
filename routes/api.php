<?php

use App\AiPro\Http\ChatController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::prefix('ai-pro/v1/sites/{publicId}')->where(['publicId' => '[a-zA-Z0-9-]+'])->controller(ChatController::class)->group(function () {
    Route::get('/config', 'config');
    Route::post('/conversations', 'conversation');
    Route::post('/messages', 'message');
});
