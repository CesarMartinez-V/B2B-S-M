<?php

use App\Http\Controllers\Portal\PortalAccountController;
use App\Http\Controllers\Portal\PortalBrandsController;
use App\Http\Controllers\Portal\PortalCatalogController;
use App\Http\Controllers\Portal\PortalDashboardController;
use App\Http\Controllers\Portal\PortalInvoicesController;
use App\Http\Controllers\Portal\PortalOrdersController;
use App\Http\Controllers\Portal\PortalProfileController;
use App\Http\Controllers\Portal\PortalQuotesController;
use App\Http\Controllers\Portal\PortalSupportController;
use Illuminate\Support\Facades\Route;

Route::prefix('portal')->group(function (): void {
    Route::get('/dashboard', [PortalDashboardController::class, 'show']);
    Route::get('/catalog', [PortalCatalogController::class, 'index']);
    Route::get('/brands', [PortalBrandsController::class, 'index']);
    Route::get('/orders', [PortalOrdersController::class, 'index']);
    Route::get('/account', [PortalAccountController::class, 'show']);
    Route::get('/invoices', [PortalInvoicesController::class, 'index']);
    Route::get('/quotes', [PortalQuotesController::class, 'index']);
    Route::get('/profile', [PortalProfileController::class, 'show']);
    Route::post('/quotes', [PortalQuotesController::class, 'store']);
    Route::post('/support/contact', [PortalSupportController::class, 'contact']);
});
