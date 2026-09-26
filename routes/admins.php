<?php

use App\Http\Controllers\LogoutController;
use App\Http\Middleware\RequireAdmin;
use App\Livewire\Admins;
use Illuminate\Support\Facades\Route;

Route::prefix('admins')->name('admins.')->middleware(['auth:web', RequireAdmin::class])->group(function () {
    Route::get('/dashboard', Admins\Dashboard::class)->name('index');
    Route::get('/ratings', Admins\Ratings::class)->name('ratings');
    Route::get('/users', Admins\Users::class)->name('users');
    Route::get('/articles', Admins\ContentManager::class)->defaults('kind', 'articles')->name('articles');
    Route::get('/products', Admins\ContentManager::class)->defaults('kind', 'products')->name('products');
    Route::get('/calendar', Admins\ContentManager::class)->defaults('kind', 'events')->name('calendar');
    Route::get('/services/website-audits', Admins\RevenueServices::class)->defaults('service', 'website-audit')->name('website-audits');
    Route::get('/services/sponsorships', Admins\RevenueServices::class)->defaults('service', 'sponsorship')->name('sponsorships');
    Route::post('/logout', LogoutController::class)->name('logout');
});
