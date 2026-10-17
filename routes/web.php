<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\Admin\AboutInfoController;
use App\Http\Controllers\Admin\BasicController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\ContactInfoController;
use App\Http\Controllers\Admin\ExperienceController;
use App\Http\Controllers\Admin\LinkController;
use App\Http\Controllers\Admin\MailNoticeController;
use App\Http\Controllers\Admin\MediaPreviewController;
use App\Http\Controllers\Admin\MenuController;
use App\Http\Controllers\Admin\ModuleController;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\PortfolioTemplateController;
use App\Http\Controllers\Admin\PostController;
use App\Http\Controllers\Admin\PrivacyPolicyController;
use App\Http\Controllers\Admin\ProjectController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\ServiceController;
use App\Http\Controllers\Admin\ServiceInfoController;
use App\Http\Controllers\Admin\SkillController;
use App\Http\Controllers\Admin\SocialLinkController;
use App\Http\Controllers\Admin\TableController;
use App\Http\Controllers\Admin\TagController;
use App\Http\Controllers\Admin\TestimonialController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\NewsletterController;
use App\Http\Controllers\PublicSiteController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\SocialLoginController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', [PublicSiteController::class, 'home'])->name('blogs.view');

Auth::routes(['verify' => true]);

Route::get('/login/google', [SocialLoginController::class, 'login'])->defaults('provider', 'google')->middleware(['guest', 'throttle:20,1'])->name('login.google');
Route::get('/login/{provider}', [SocialLoginController::class, 'login'])->whereIn('provider', ['linkedin', 'github'])->middleware(['guest', 'throttle:20,1'])->name('login.social');
Route::get('/login/{provider}/callback', [SocialLoginController::class, 'callback'])->whereIn('provider', ['google', 'linkedin', 'github'])->middleware('throttle:20,1')->name('login.social.callback');
Route::post('/account/connections/{provider}', [SocialLoginController::class, 'connect'])->whereIn('provider', ['google', 'linkedin', 'github'])->middleware(['auth', 'throttle:10,1'])->name('account.connections');
Route::prefix('workspace/email-notices')->controller(MailNoticeController::class)->group(function () {
    Route::get('/', 'index')->name('workspace.email-notices');
    Route::get('/compose', 'compose')->name('workspace.email-notices.compose');
    Route::get('/templates', 'templatePage')->name('workspace.email-notices.templates');
    Route::get('/history', 'history')->name('workspace.email-notices.history');
    Route::get('/recipients', 'recipients');
    Route::get('/data/templates', 'templates');
    Route::post('/templates', 'saveTemplate')->middleware('throttle:20,1,notice-templates');
    Route::get('/templates/{template}', 'template')->whereNumber('template');
    Route::put('/templates/{template}', 'saveTemplate')->whereNumber('template')->middleware('throttle:20,1,notice-templates');
    Route::delete('/templates/{template}', 'deleteTemplate')->whereNumber('template')->middleware('throttle:20,1,notice-templates');
    Route::post('/preview', 'preview')->middleware('throttle:30,1,notice-preview');
    Route::post('/send', 'send')->middleware('throttle:5,1,notice-send');
    Route::get('/campaigns', 'campaigns');
    Route::get('/campaigns/{campaign}', 'campaign')->whereNumber('campaign');
    Route::post('/campaigns/{campaign}/retry', 'retry')->whereNumber('campaign')->middleware('throttle:5,1,notice-retry');
});

Route::get('/workspace/data/{resource}', TableController::class)->middleware('auth')->whereIn('resource', array_keys(config('admin')))->name('workspace.data');

Route::get('/workspace/media/{resource}/{record}/{field}', MediaPreviewController::class)->middleware('auth')->whereIn('resource', ['posts', 'projects', 'basics'])->whereNumber('record')->where('field', '[a-z_]+')->name('workspace.media');

Route::middleware(['auth'])->group(function () {
    Route::get('/workspace/portfolio-templates', [PortfolioTemplateController::class, 'index'])->name('workspace.portfolio-templates');
    Route::patch('/workspace/portfolio-templates', [PortfolioTemplateController::class, 'update'])->middleware('throttle:20,1')->name('workspace.portfolio-templates.update');
    Route::get('logout', fn () => redirect('/home'));
    Route::get('/home', [HomeController::class, 'index'])->name('home');
    Route::get('/account/profile', [AccountController::class, 'profile'])->name('account.profile');
    Route::get('/account/settings', [AccountController::class, 'settings'])->name('account.settings');
    Route::patch('/account/settings', [AccountController::class, 'update'])->middleware('throttle:10,1,account-details')->name('account.update');
    Route::put('/account/password', [AccountController::class, 'password'])->middleware('throttle:5,1,account-password')->name('account.password');
});

Route::get('services/{slug}', [PublicSiteController::class, 'service'])->whereIn('slug', array_keys(config('site.services')))->name('public.services.show');

Route::middleware('auth')->group(function () {
    Route::resources([
        //    Security section
        'menus' => MenuController::class,
        'modules' => ModuleController::class,
        'links' => LinkController::class,
        'permissions' => PermissionController::class,
        'roles' => RoleController::class,
        'users' => UserController::class,

        //    Blog section
        'categories' => CategoryController::class,
        'tags' => TagController::class,
        'posts' => PostController::class,
        'about-info' => AboutInfoController::class,
        'service-info' => ServiceInfoController::class,
        'privacy-policies' => PrivacyPolicyController::class,
        'contact-info' => ContactInfoController::class,

        //    Portfolio section
        'basics' => BasicController::class,
        'social-links' => SocialLinkController::class,
        'skills' => SkillController::class,
        'services' => ServiceController::class,
        'projects' => ProjectController::class,
        'experiences' => ExperienceController::class,
        'testimonials' => TestimonialController::class,
    ]);
});

Route::get('blogs', [PublicSiteController::class, 'blog'])->name('blogs.index');
Route::get('blogs/{id}', [PublicSiteController::class, 'article'])->whereNumber('id')->name('blogs.show');
Route::get('portfolios', [PublicSiteController::class, 'portfolio'])->name('portfolios.index');
Route::get('portfolios/{key}/contact', [PublicSiteController::class, 'portfolioContact'])->name('portfolios.contact');
Route::post('portfolios/{key}/inquiries', [PublicSiteController::class, 'portfolioInquiry'])->middleware('throttle:5,1')->name('portfolios.inquiries');
Route::get('portfolios/{key}', [PublicSiteController::class, 'portfolio'])->name('portfolios.show');
Route::resource('contacts', ContactController::class)->only(['index', 'show', 'destroy'])->middleware('auth');
Route::post('contacts', [ContactController::class, 'store'])->middleware('throttle:5,1')->name('contacts.store');
Route::post('newsletters', [NewsletterController::class, 'store'])->middleware('throttle:5,1')->name('newsletters.store');
Route::resource('comments', CommentController::class)->only(['store', 'edit', 'update', 'destroy'])->middleware(['auth', 'throttle:20,1']);

Route::post('user-enable-or-disable', [UserController::class, 'user_handler'])->middleware('auth')->name('users.enable-or-disable');
Route::post('portfolio-enable-or-disable', [UserController::class, 'user_portfolio_handler'])->middleware('auth')->name('users.portfolio-enable-or-disable');
Route::post('get-permissions', [PermissionController::class, 'get_permissions'])->middleware('auth')->name('permissions.view');
Route::post('cr-permission', [PermissionController::class, 'permission_handler'])->middleware('auth')->name('permissions.store_or_destroy');
Route::post('basics-file-delete', [BasicController::class, 'delete_file'])->middleware('auth')->name('basics.delete-files');
Route::post('project-file-delete', [ProjectController::class, 'delete_file'])->middleware('auth')->name('projects.delete-files');
Route::get('get-users', [UserController::class, 'get_users_ajax'])->middleware('auth')->name('users.view');
Route::get('get-posts', [PostController::class, 'get_posts_ajax'])->middleware('auth')->name('posts.view');
Route::post('reply', [CommentController::class, 'replyStore'])->middleware(['auth', 'throttle:20,1'])->name('reply.store');
Route::match(['get', 'post'], 'blogs/search', [PublicSiteController::class, 'blog'])->name('blogs.search');
Route::get('blogs/category/{category_id}', [PublicSiteController::class, 'blog'])->name('blogs.category');
Route::get('about/info', [PublicSiteController::class, 'about'])->name('blogs.about');
Route::get('service/info', [PublicSiteController::class, 'offerings'])->name('blogs.services');
Route::get('privacy-n-policy', [PublicSiteController::class, 'privacy'])->name('blogs.privacy-policies');
Route::get('contact/info', [PublicSiteController::class, 'contact'])->name('blogs.contact');

Route::get('work', [PublicSiteController::class, 'work'])->name('public.work');
Route::get('work/{id}', [PublicSiteController::class, 'project'])->whereNumber('id')->name('public.work.show');
Route::post('inquiries', [PublicSiteController::class, 'inquiry'])->middleware('throttle:5,1')->name('public.inquiries');
Route::permanentRedirect('about', '/about/info');
Route::permanentRedirect('contact', '/contact/info');
Route::permanentRedirect('blog', '/blogs');
Route::get('healthz', fn () => response()->json(['status' => 'ok']))->name('health');

Route::get('sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');
Route::get('sitemaps/pages.xml', [SitemapController::class, 'pages']);
Route::get('sitemaps/{type}-{page}.xml', [SitemapController::class, 'records'])->whereIn('type', ['articles', 'projects', 'portfolios'])->whereNumber('page');
Route::get('robots.txt', [SitemapController::class, 'robots']);
