<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', \App\Livewire\Pages\Index::class)->name('pages.index');
Route::get('/services/seo-audit', \App\Livewire\Services\SiteInspector::class)->name('services.seo-audit');
Route::get('/services/web-crawler', \App\Livewire\Services\SiteInspector::class)->name('services.web-crawler');
Route::get('/services/site-report/{report}', function (\App\Models\SiteReport $report) {
    abort_unless(hash_equals($report->owner_hash, hash('sha256', session()->getId())), 404);

    return view('reports.site-download', compact('report'));
})->name('services.site-report');
Route::get('/services/quote-builder', \App\Livewire\Services\QuoteBuilder::class)->name('services.quote-builder');
Route::get('/services/text-toolkit', \App\Livewire\Services\TextToolkit::class)->name('services.text-toolkit');
Route::get('/services/percentage-calculator', \App\Livewire\Services\PercentageCalculator::class)->name('services.percentage-calculator');
Route::get('/services/unit-converter', \App\Livewire\Services\UnitConverter::class)->name('services.unit-converter');
Route::get('/services/date-difference', \App\Livewire\Services\DateDifference::class)->name('services.date-difference');
Route::get('/services/age-calculator', \App\Livewire\Services\AgeCalculator::class)->name('services.age-calculator');
Route::get('/services/word-counter', \App\Livewire\Services\WordCounter::class)->name('services.word-counter');
Route::get('/services/timezone-converter', \App\Livewire\Services\TimezoneConverter::class)->name('services.timezone-converter');
Route::prefix('pages')->group(function () {
    Route::get('/products', \App\Livewire\Pages\Products::class)->name('pages.products');
    Route::get('/articles', \App\Livewire\Pages\Articles::class)->name('pages.articles');
    Route::get('/articles/{slug}', \App\Livewire\Pages\PostShow::class)->name('pages.postshow');
    Route::get('/about', \App\Livewire\Pages\About::class)->name('pages.about');
    Route::get('/contact', \App\Livewire\Pages\Contact::class)->name('pages.contact');
    Route::get('/policy', \App\Livewire\Pages\Policy::class)->name('pages.policy');
    Route::get('/quotes', \App\Livewire\Pages\Notes::class)->name('pages.quotes');
});
Route::get('/products/{product}/visit', \App\Http\Controllers\AffiliateRedirectController::class)
    ->whereNumber('product')->middleware('throttle:60,1')->name('products.visit');

Route::prefix('services')->middleware('auth:web')->group(function () {
    Route::get('/affiliates', \App\Livewire\Services\Affiliates::class)->name('services.affiliates');
    Route::get('/calendar', \App\Livewire\Services\Calendar::class)->name('services.calendar');
});
// Services
// add prefix for services
Route::prefix('services')->group(function () {
    Route::get('/users', \App\Livewire\Services\Users::class)->name('services.users');
});

Route::prefix('admin')->middleware(['auth:web', \App\Http\Middleware\RequireAdmin::class])->group(function () {
    Route::get('/dashboard', \App\Livewire\Admins\Dashboard::class)->name('admins.index');
    Route::get('/users', \App\Livewire\Services\Users::class)->name('admins.users');
    Route::get('/calendar', \App\Livewire\Services\Calendar::class)->name('admins.calendar');
    Route::get('/articles', \App\Livewire\Users\Articles::class)->name('admins.articles');
    Route::get('/products', \App\Livewire\Services\Affiliates::class)->name('admins.products');
    Route::get('/profile', \App\Livewire\Users\Profile::class)->name('admins.profile');
    Route::get('/setting', \App\Livewire\Users\Setting::class)->name('admins.setting');
    Route::get('/pages/articles', \App\Livewire\Pages\Articles::class)->name('admins.pages.articles');
    Route::get('/pages/articles/{slug}', \App\Livewire\Pages\PostShow::class)->name('admins.pages.postshow');
    Route::get('/pages/products', \App\Livewire\Pages\Products::class)->name('admins.pages.products');
    Route::get('/pages/quotes', \App\Livewire\Pages\Notes::class)->name('admins.pages.quotes');
    Route::get('/about', \App\Livewire\Pages\About::class)->name('admins.about');
    Route::get('/contact', \App\Livewire\Pages\Contact::class)->name('admins.contact');
    Route::get('/policy', \App\Livewire\Pages\Policy::class)->name('admins.policy');
    Route::post('/logout', function (Request $request) {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('pages.index');
    })->name('admins.logout');
});

// users
Route::prefix('users')->middleware('auth:web')->group(function () {
    Route::get('/calendar', \App\Livewire\Services\Calendar::class)->name('users.calendar');
    Route::get('/products', \App\Livewire\Services\Affiliates::class)->name('users.products');
    Route::get('/articles', \App\Livewire\Users\Articles::class)->name('users.articles');
    Route::get('/dashboard', \App\Livewire\Users\Index::class)->name('users.index');
    Route::get('/profile', \App\Livewire\Users\Profile::class)->name('users.profile');
    Route::get('/setting', \App\Livewire\Users\Setting::class)->name('users.setting');
});

// add the logout route
Route::post('/logout', function (Request $request) {
    Auth::guard('web')->logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect()->route('pages.index');
})->middleware('auth:web')->name('auth.logout');

// add auth middleware for protected routes




// login and registration routes
Route::get('/login', \App\Livewire\Auths\Login::class)->name('auth.login');
Route::get('/register', \App\Livewire\Auths\Register::class)->name('auth.register');
