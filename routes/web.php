<?php

use Illuminate\Support\Facades\Route;

// Public service routes precede the legacy admin service resource routes.
require __DIR__.'/website.php';
require __DIR__.'/auth.php';
require __DIR__.'/admin.php';
require __DIR__.'/portfolio.php';

require __DIR__.'/ai_pro.php';

// Run the web middleware for missing workspace pages so authenticated errors
// can retain their session and permission-filtered navigation.
Route::fallback(fn () => abort(404))
    ->where('fallbackPlaceholder', '(?:workspace|home|account|permissions|'.implode('|', array_keys(config('admin'))).')(?:/.*)?');
