<?php

use App\Http\Controllers\EconomicPerformanceController;
use App\Http\Controllers\FinanceSourceController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PublicBusinessController;
use App\Http\Controllers\WishController;
use Illuminate\Support\Facades\Route;

// Public pages need no login and never receive admin fields (privacy-rules.md, P8).
Route::get('/', HomeController::class)->name('home');

Route::name('public.')->group(function () {
    Route::get('economic-performance', EconomicPerformanceController::class)->name('economic-performance');
    // The stored COA report behind a signed-off figure; the page number travels in the link's #page fragment.
    Route::get('finance-sources/{figure:public_id}', FinanceSourceController::class)->middleware('throttle:finance-sources')->name('finance-sources.show');
    Route::get('businesses', [PublicBusinessController::class, 'index'])->name('businesses.index');
    Route::get('businesses/{business:public_id}', [PublicBusinessController::class, 'show'])->name('businesses.show');
    Route::get('wishes', [WishController::class, 'index'])->name('wishes.index');
    Route::post('wishes', [WishController::class, 'store'])->middleware('throttle:wishes')->name('wishes.store');
    Route::put('wishes/{wish}', [WishController::class, 'update'])->middleware('throttle:wishes')->name('wishes.update');
});
