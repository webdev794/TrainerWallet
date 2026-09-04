<?php

use App\Http\Controllers\BillingController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\ClientImportController;
use App\Http\Controllers\ClientInviteController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\InvoicePaymentController;
use App\Http\Controllers\PackageController;
use App\Http\Controllers\RecurringInvoiceController;
use App\Http\Controllers\ReportsController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\StripeConnectController;
use App\Http\Controllers\TrainerBookingController;
use App\Http\Controllers\TrainerReviewController;
use App\Http\Controllers\TrainingSessionController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'role:trainer', 'onboarded'])->group(function (): void {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/clients', [ClientController::class, 'index'])->name('clients.index');
    Route::post('/clients', [ClientController::class, 'store'])->name('clients.store');
    Route::put('/clients/{client}', [ClientController::class, 'update'])->name('clients.update');
    Route::delete('/clients/{client}', [ClientController::class, 'destroy'])->name('clients.destroy');
    Route::post('/clients/import', ClientImportController::class)->name('clients.import');
    Route::post('/clients/{client}/invite', ClientInviteController::class)->name('clients.invite');

    Route::get('/packages', [PackageController::class, 'index'])->name('packages.index');
    Route::post('/packages', [PackageController::class, 'store'])->name('packages.store');
    Route::put('/packages/{package}', [PackageController::class, 'update'])->name('packages.update');
    Route::delete('/packages/{package}', [PackageController::class, 'destroy'])->name('packages.destroy');

    Route::get('/sessions', [TrainingSessionController::class, 'index'])->name('sessions.index');
    Route::post('/sessions', [TrainingSessionController::class, 'store'])->name('sessions.store');
    Route::put('/sessions/{session}', [TrainingSessionController::class, 'update'])->name('sessions.update');
    Route::delete('/sessions/{session}', [TrainingSessionController::class, 'destroy'])->name('sessions.destroy');

    Route::get('/invoices', [InvoiceController::class, 'index'])->name('invoices.index');
    Route::get('/invoices/create', [InvoiceController::class, 'create'])->middleware('quota')->name('invoices.create');
    Route::post('/invoices', [InvoiceController::class, 'store'])->middleware('quota')->name('invoices.store');
    Route::get('/invoices/{invoice}', [InvoiceController::class, 'show'])->name('invoices.show');
    Route::get('/invoices/{invoice}/edit', [InvoiceController::class, 'edit'])->name('invoices.edit');
    Route::put('/invoices/{invoice}', [InvoiceController::class, 'update'])->name('invoices.update');
    Route::post('/invoices/{invoice}/send', [InvoiceController::class, 'send'])->name('invoices.send');
    Route::post('/invoices/{invoice}/void', [InvoiceController::class, 'void'])->name('invoices.void');
    Route::delete('/invoices/{invoice}', [InvoiceController::class, 'destroy'])->name('invoices.destroy');
    Route::get('/invoices/{invoice}/pdf', [InvoiceController::class, 'pdf'])->name('invoices.pdf');

    Route::post('/invoices/{invoice}/payments', [InvoicePaymentController::class, 'store'])->name('invoices.payments.store');
    Route::post('/payments/{payment}/confirm', [InvoicePaymentController::class, 'confirm'])->name('payments.confirm');
    Route::post('/payments/{payment}/refund', [InvoicePaymentController::class, 'refund'])->name('payments.refund');
    Route::get('/payments/{payment}/receipt', [InvoicePaymentController::class, 'receipt'])->name('payments.receipt');
    Route::get('/payments', [InvoicePaymentController::class, 'index'])->name('payments.index');

    Route::get('/settings', [SettingsController::class, 'edit'])->name('settings.edit');
    Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update');
    Route::post('/settings/stripe/connect', [StripeConnectController::class, 'connect'])->name('settings.stripe.connect');
    Route::get('/settings/stripe/return', [StripeConnectController::class, 'return'])->name('settings.stripe.return');

    Route::get('/recurring', [RecurringInvoiceController::class, 'index'])->name('recurring.index');
    Route::post('/recurring', [RecurringInvoiceController::class, 'store'])->name('recurring.store');
    Route::put('/recurring/{recurring}', [RecurringInvoiceController::class, 'update'])->name('recurring.update');
    Route::delete('/recurring/{recurring}', [RecurringInvoiceController::class, 'destroy'])->name('recurring.destroy');
    Route::post('/recurring/{recurring}/run', [RecurringInvoiceController::class, 'runNow'])->name('recurring.run');

    Route::get('/bookings', [TrainerBookingController::class, 'index'])->name('bookings.index');
    Route::put('/bookings/{booking}', [TrainerBookingController::class, 'update'])->name('bookings.update');

    Route::get('/reviews', [TrainerReviewController::class, 'index'])->name('reviews.index');

    Route::get('/reports', [ReportsController::class, 'index'])->name('reports.index');
    Route::get('/reports/payments.csv', [ReportsController::class, 'exportPayments'])->name('reports.payments.csv');

    Route::get('/billing', [BillingController::class, 'show'])->name('billing.show');
    Route::post('/billing/checkout', [BillingController::class, 'checkout'])->name('billing.checkout');
    Route::post('/billing/portal', [BillingController::class, 'portal'])->name('billing.portal');
});
