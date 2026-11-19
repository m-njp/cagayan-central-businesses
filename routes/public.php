<?php

use App\Http\Controllers\EconomicPerformanceController;
use App\Http\Controllers\EconomicPerformanceTownController;
use App\Http\Controllers\FinanceSourceController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PublicBusinessController;
use App\Http\Controllers\PublicFinanceArchiveController;
use App\Http\Controllers\PublicReportController;
use App\Http\Controllers\PublicSourcesController;
use App\Http\Controllers\PublicWorkbookController;
use App\Http\Controllers\WishController;
use Illuminate\Support\Facades\Route;

// Public pages need no login and never receive admin fields (privacy-rules.md, P8).
Route::get('/', HomeController::class)->name('home');

Route::name('public.')->group(function () {
    Route::get('economic-performance', EconomicPerformanceController::class)->name('economic-performance');
    Route::get('economic-performance/towns/{town:psgc_code}', EconomicPerformanceTownController::class)
        ->where('town', '[0-9]{10}')->middleware('throttle:finance-sources')->name('economic-performance.town');
    Route::get('about-the-data', PublicSourcesController::class)->name('sources');
    Route::redirect('sources', '/about-the-data');
    Route::get('about-the-data/download', PublicFinanceArchiveController::class)->middleware('throttle:finance-archives')->name('sources-download');
    Route::get('about-the-data/reports/{report}', PublicReportController::class)
        ->where('report', '[a-f0-9]{64}')->middleware('throttle:finance-sources')->name('source-reports.show');
    Route::get('about-the-data/workbooks/{workbook}', PublicWorkbookController::class)
        ->where('workbook', '[a-f0-9]{64}')->middleware('throttle:finance-sources')->name('source-workbooks.show');
    // The stored COA report behind a signed-off figure; the page number travels in the link's #page fragment.
    Route::get('finance-sources/{figure:public_id}', FinanceSourceController::class)->middleware('throttle:finance-sources')->name('finance-sources.show');
    Route::get('businesses', [PublicBusinessController::class, 'index'])->name('businesses.index');
    Route::get('businesses/{business:public_id}', [PublicBusinessController::class, 'show'])->name('businesses.show');
    Route::get('wishes', [WishController::class, 'index'])->name('wishes.index');
    Route::post('wishes', [WishController::class, 'store'])->middleware('throttle:wishes')->name('wishes.store');
    Route::put('wishes/{wish}', [WishController::class, 'update'])->middleware('throttle:wishes')->name('wishes.update');
});
