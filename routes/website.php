<?php

use App\Http\Controllers\Website\CommentController;
use App\Http\Controllers\Website\NewsletterController;
use App\Http\Controllers\Website\SiteController;
use App\Http\Controllers\Website\SitemapController;
use Illuminate\Support\Facades\Route;

Route::get('/', [SiteController::class, 'home'])->name('blogs.view');

Route::get('services/{slug}', [SiteController::class, 'service'])->whereIn('slug', array_keys(config('site.services')))->name('public.services.show');

Route::get('blogs', [SiteController::class, 'blog'])->name('blogs.index');

Route::get('blogs/{id}', [SiteController::class, 'article'])->whereNumber('id')->name('blogs.show');

Route::post('newsletters', [NewsletterController::class, 'store'])->middleware('throttle:5,1')->name('newsletters.store');

Route::resource('comments', CommentController::class)->only(['store', 'edit', 'update', 'destroy'])->middleware(['auth', 'throttle:20,1']);

Route::post('reply', [CommentController::class, 'replyStore'])->middleware(['auth', 'throttle:20,1'])->name('reply.store');

Route::match(['get', 'post'], 'blogs/search', [SiteController::class, 'blog'])->name('blogs.search');

Route::get('blogs/category/{category_id}', [SiteController::class, 'blog'])->name('blogs.category');

Route::get('about/info', [SiteController::class, 'about'])->name('blogs.about');

Route::get('service/info', [SiteController::class, 'offerings'])->name('blogs.services');

Route::get('privacy-n-policy', [SiteController::class, 'privacy'])->name('blogs.privacy-policies');

Route::get('contact/info', [SiteController::class, 'contact'])->name('blogs.contact');

Route::get('work', [SiteController::class, 'work'])->name('public.work');

Route::get('work/{id}', [SiteController::class, 'project'])->whereNumber('id')->name('public.work.show');

Route::post('inquiries', [SiteController::class, 'inquiry'])->middleware('throttle:5,1')->name('public.inquiries');

Route::permanentRedirect('about', '/about/info');

Route::permanentRedirect('contact', '/contact/info');

Route::permanentRedirect('blog', '/blogs');

Route::get('healthz', fn () => response()->json(['status' => 'ok']))->name('health');

Route::get('sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');

Route::get('sitemaps/pages.xml', [SitemapController::class, 'pages']);

Route::get('sitemaps/{type}-{page}.xml', [SitemapController::class, 'records'])->whereIn('type', ['articles', 'projects', 'portfolios'])->whereNumber('page');

Route::get('robots.txt', [SitemapController::class, 'robots']);
