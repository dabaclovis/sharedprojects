<?php

use App\Http\Controllers\LogoutController;
use App\Livewire\Auths;
use App\Livewire\Pages;
use Illuminate\Support\Facades\Route;

Route::get('/', Pages\Index::class)->name('home');

require __DIR__.'/pages.php';
require __DIR__.'/users.php';
require __DIR__.'/admins.php';
require __DIR__.'/legacy.php';

Route::get('/login', Auths\Login::class)->name('auth.login');
Route::get('/register', Auths\Register::class)->name('auth.register');
Route::post('/logout', LogoutController::class)->middleware('auth:web')->name('auth.logout');
