<?php

use App\Http\Controllers\Portal\PortalBookingController;
use App\Http\Controllers\Portal\PortalDashboardController;
use App\Http\Controllers\Portal\PortalInvoiceController;
use App\Http\Controllers\Portal\PortalInvoiceListController;
use App\Http\Controllers\Portal\PortalReceiptController;
use App\Http\Controllers\Portal\PortalReviewController;
use App\Http\Controllers\Portal\PortalSessionController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'role:client'])
    ->prefix('portal')
    ->name('portal.')
    ->group(function (): void {
        Route::get('/', [PortalDashboardController::class, 'index'])->name('index');

        Route::get('/invoices', [PortalInvoiceListController::class, 'index'])->name('invoices');
        Route::get('/invoices/{invoice}', [PortalInvoiceController::class, 'show'])->name('invoices.show');

        Route::get('/sessions', [PortalSessionController::class, 'index'])->name('sessions');

        Route::get('/receipts', [PortalReceiptController::class, 'index'])->name('receipts');
        Route::get('/receipts/{payment}/download', [PortalReceiptController::class, 'download'])->name('receipts.download');

        Route::get('/book', [PortalBookingController::class, 'create'])->name('book');
        Route::get('/bookings', [PortalBookingController::class, 'index'])->name('bookings');
        Route::post('/bookings', [PortalBookingController::class, 'store'])->name('bookings.store');
        Route::put('/bookings/{booking}', [PortalBookingController::class, 'update'])->name('bookings.update');

        Route::get('/reviews', [PortalReviewController::class, 'index'])->name('reviews');
        Route::post('/reviews', [PortalReviewController::class, 'store'])->name('reviews.store');
        Route::put('/reviews/{review}', [PortalReviewController::class, 'update'])->name('reviews.update');
        Route::delete('/reviews/{review}', [PortalReviewController::class, 'destroy'])->name('reviews.destroy');
    });
