<?php

use App\Http\Controllers\Capitol\ActivityLogController;
use App\Http\Controllers\Capitol\AnalyticsController;
use App\Http\Controllers\Capitol\BusinessCategoryController;
use App\Http\Controllers\Capitol\BusinessController;
use App\Http\Controllers\Capitol\BusinessDirectoryController;
use App\Http\Controllers\Capitol\BusinessDocumentController;
use App\Http\Controllers\Capitol\BusinessReviewController;
use App\Http\Controllers\Capitol\CommunityWishController;
use App\Http\Controllers\Capitol\DashboardController;
use App\Http\Controllers\Capitol\DataEntryController;
use App\Http\Controllers\Capitol\FinanceFigureController;
use App\Http\Controllers\Capitol\FinanceReviewController;
use App\Http\Controllers\Capitol\FinanceUploadController;
use App\Http\Controllers\Capitol\ProfileController;
use App\Http\Controllers\Capitol\SessionActivityController;
use App\Http\Middleware\PreventIndexing;
use Illuminate\Support\Facades\Route;

Route::middleware([PreventIndexing::class, 'auth', 'can:capitol'])
    ->prefix('capitol')->name('capitol.')->group(function (): void {
        Route::get('/', DashboardController::class)->name('dashboard');
        Route::get('data-entry', DataEntryController::class)->name('data-entry');
        Route::post('session-activity', SessionActivityController::class)->middleware('throttle:workspace-activity')->name('session.activity');
        Route::get('community-wishes', CommunityWishController::class)->middleware('can:community-wishes')->name('wishes.index');
        Route::get('activity-logs', ActivityLogController::class)->middleware('can:activity-logs')->name('logs.index');
        Route::middleware('can:manage-categories')->prefix('business-categories')->name('categories.')->group(function (): void {
            Route::get('/', [BusinessCategoryController::class, 'index'])->name('index');
            Route::post('/', [BusinessCategoryController::class, 'storeCategory'])->name('store');
            Route::post('types', [BusinessCategoryController::class, 'storeType'])->name('types.store');
            Route::put('types/{type:code}', [BusinessCategoryController::class, 'updateType'])->name('types.update');
            Route::put('{category:code}', [BusinessCategoryController::class, 'updateCategory'])->name('update');
        });
        Route::get('businesses', BusinessDirectoryController::class)->name('businesses.index');
        Route::get('businesses/create', [BusinessController::class, 'create'])->name('businesses.create');
        Route::post('businesses', [BusinessController::class, 'store'])->name('businesses.store');
        Route::get('businesses/{business:public_id}', [BusinessController::class, 'show'])->name('businesses.show');
        Route::get('businesses/{business:public_id}/edit', [BusinessController::class, 'edit'])->name('businesses.edit');
        Route::put('businesses/{business:public_id}', [BusinessController::class, 'update'])->name('businesses.update');
        Route::post('businesses/{business:public_id}/reviews', BusinessReviewController::class)->name('businesses.reviews.store');
        Route::get('businesses/{business:public_id}/documents/{document:public_id}', BusinessDocumentController::class)
            ->scopeBindings()->middleware('throttle:registry-documents')->name('businesses.documents.show');
        Route::get('analytics', AnalyticsController::class)->name('analytics');
        Route::get('finances', [FinanceReviewController::class, 'index'])->name('finances.index');
        Route::get('finances/{town:psgc_code}/{year}', [FinanceReviewController::class, 'show'])->whereNumber('year')->name('finances.show');
        Route::post('finances/{town:psgc_code}/{year}/figures', [FinanceFigureController::class, 'store'])->whereNumber('year')->middleware('throttle:finance-writes')->name('finances.figures.store');
        Route::post('finances/{town:psgc_code}/{year}/uploads', [FinanceUploadController::class, 'store'])->whereNumber('year')->middleware('throttle:finance-writes')->name('finances.uploads.store');
        Route::post('finances/{town:psgc_code}/{year}/reviews', [FinanceReviewController::class, 'store'])->whereNumber('year')->middleware('can:finance-review')->name('finances.reviews.store');
        Route::get('profile', [ProfileController::class, 'show'])->name('profile.show');
        Route::patch('profile', [ProfileController::class, 'update'])->name('profile.update');
    });
