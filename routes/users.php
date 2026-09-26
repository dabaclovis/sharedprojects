<?php

use App\Livewire\Users;
use Illuminate\Support\Facades\Route;

Route::prefix('users')->name('users.')->middleware('auth:web')->group(function () {
    Route::get('/dashboard', Users\Index::class)->name('index');
    Route::get('/articles', Users\Articles::class)->name('articles');
    Route::get('/products', Users\Products::class)->name('products');
    Route::get('/calendar', Users\Calendar::class)->name('calendar');
    Route::get('/profile', Users\Profile::class)->name('profile');
    Route::get('/setting', Users\Setting::class)->name('setting');
});
