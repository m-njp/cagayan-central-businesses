<?php

use App\Http\Controllers\DemoLoginController;
use App\Http\Middleware\PreventIndexing;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Http\Controllers\AuthenticatedSessionController;

require __DIR__.'/public.php';
require __DIR__.'/capitol.php';

// The admin login lives only at the path in .env and is never linked, listed, or indexed
// (decisions.md, D20). GET and POST share the path, so the page posts to its own URL. The
// admin.* names keep it in its own Wayfinder module that no page imports, so the path never
// enters the JS bundle. Fortify's own routes are off (FortifyServiceProvider).
Route::middleware(['guest', PreventIndexing::class])->group(function () {
    Route::inertia(config('cagayan.admin.login_path'), 'auth/login')->name('admin.login');
    Route::post(config('cagayan.admin.login_path'), [AuthenticatedSessionController::class, 'store'])
        ->middleware('throttle:login')
        ->name('admin.login.store');
    // Local machines only. The controller answers 404 anywhere else (decisions.md, D33).
    Route::post(config('cagayan.admin.login_path').'/demo/{role}', DemoLoginController::class)->name('admin.login.demo');
});

Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->middleware('auth')->name('logout');
