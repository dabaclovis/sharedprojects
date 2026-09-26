<?php

use App\Http\Controllers\AffiliateRedirectController;
use App\Http\Controllers\BusinessReportController;
use App\Http\Controllers\SiteReportController;
use App\Livewire\Pages;
use Illuminate\Support\Facades\Route;

Route::prefix('pages')->name('pages.')->group(function () {
    Route::get('/', Pages\Index::class)->name('index');
    Route::get('/articles', Pages\Articles::class)->name('articles');
    Route::get('/articles/{slug}', Pages\PostShow::class)->name('postshow');
    Route::get('/products', Pages\Products::class)->name('products');
    Route::get('/quotes', Pages\Notes::class)->name('quotes');
    Route::get('/about', Pages\About::class)->name('about');
    Route::get('/contact', Pages\Contact::class)->name('contact');
    Route::get('/policy', Pages\Policy::class)->name('policy');
    Route::get('/business', Pages\BusinessServices::class)->name('business');
    Route::get('/business-report/{order:reference}', BusinessReportController::class)->middleware('signed')->name('business-report');
    Route::get('/seo-audit', Pages\SiteInspector::class)->name('seo-audit');
    Route::get('/web-crawler', Pages\SiteInspector::class)->name('web-crawler');
    Route::get('/site-report/{report}', SiteReportController::class)->name('site-report');
    Route::get('/quote-builder', Pages\QuoteBuilder::class)->name('quote-builder');
    Route::get('/text-toolkit', Pages\TextToolkit::class)->name('text-toolkit');
    Route::get('/percentage-calculator', Pages\PercentageCalculator::class)->name('percentage-calculator');
    Route::get('/unit-converter', Pages\UnitConverter::class)->name('unit-converter');
    Route::get('/date-difference', Pages\DateDifference::class)->name('date-difference');
    Route::get('/age-calculator', Pages\AgeCalculator::class)->name('age-calculator');
    Route::get('/word-counter', Pages\WordCounter::class)->name('word-counter');
    Route::get('/timezone-converter', Pages\TimezoneConverter::class)->name('timezone-converter');
    Route::get('/products/{product}/visit', AffiliateRedirectController::class)->whereNumber('product')->middleware('throttle:60,1')->name('products.visit');
});
