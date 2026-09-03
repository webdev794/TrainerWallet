<?php

use App\Http\Controllers\Portal\PortalDashboardController;
use App\Http\Controllers\Portal\PortalInvoiceController;
use App\Http\Controllers\Portal\PortalSessionController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'role:client'])
    ->prefix('portal')
    ->name('portal.')
    ->group(function (): void {
        Route::get('/', [PortalDashboardController::class, 'index'])->name('index');
        Route::get('/sessions', [PortalSessionController::class, 'index'])->name('sessions');
        Route::get('/invoices/{invoice}', [PortalInvoiceController::class, 'show'])->name('invoices.show');
    });
