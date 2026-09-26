<?php

use App\Http\Controllers\AffiliateRedirectController;
use App\Http\Controllers\BusinessReportController;
use App\Http\Controllers\LogoutController;
use App\Http\Controllers\SiteReportController;
use App\Http\Middleware\RequireAdmin;
use Illuminate\Support\Facades\Route;

// Keep old bookmarks working while all application links use the three areas.
foreach (['business', 'seo-audit', 'web-crawler', 'quote-builder', 'text-toolkit', 'percentage-calculator', 'unit-converter', 'date-difference', 'age-calculator', 'word-counter', 'timezone-converter'] as $tool) {
    Route::get('/services/'.$tool, fn () => redirect()->route('pages.'.$tool))->name('services.'.$tool);
}
// Signed report paths must remain unchanged for previously issued links.
Route::get('/services/business-report/{order:reference}', BusinessReportController::class)->middleware('signed')->name('services.business-report');
Route::get('/services/site-report/{report}', SiteReportController::class)->name('services.site-report');
Route::get('/services/affiliates', fn () => redirect()->route('users.products'))->middleware('auth:web')->name('services.affiliates');
Route::get('/services/calendar', fn () => redirect()->route('users.calendar'))->middleware('auth:web')->name('services.calendar');
Route::get('/services/users', fn () => redirect()->route('admins.users'))->middleware(['auth:web', RequireAdmin::class])->name('services.users');
Route::get('/products/{product}/visit', AffiliateRedirectController::class)->whereNumber('product')->middleware('throttle:60,1')->name('products.visit');

Route::prefix('admin')->middleware(['auth:web', RequireAdmin::class])->group(function () {
    foreach (['dashboard' => 'index', 'users' => 'users', 'articles' => 'articles', 'products' => 'products', 'calendar' => 'calendar', 'services/website-audits' => 'website-audits', 'services/sponsorships' => 'sponsorships'] as $path => $name) {
        Route::get('/'.$path, fn () => redirect()->route('admins.'.$name));
    }
    foreach (['profile', 'setting'] as $page) {
        Route::get('/'.$page, fn () => redirect()->route('users.'.$page))->name('admins.'.$page);
    }
    foreach (['about', 'contact', 'policy'] as $page) {
        Route::get('/'.$page, fn () => redirect()->route('pages.'.$page))->name('admins.'.$page);
    }
    foreach (['articles', 'products', 'quotes'] as $page) {
        Route::get('/pages/'.$page, fn () => redirect()->route('pages.'.$page))->name('admins.pages.'.$page);
    }
    Route::get('/pages/articles/{slug}', fn (string $slug) => redirect()->route('pages.postshow', $slug))->name('admins.pages.postshow');
    foreach (['seo-audit', 'web-crawler'] as $tool) {
        Route::get('/services/'.$tool, fn () => redirect()->route('pages.'.$tool))->name('admins.'.$tool);
    }
    Route::post('/logout', LogoutController::class);
});
