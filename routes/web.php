<?php

use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\Payments\PublicInvoiceController;
use App\Http\Controllers\Payments\PublicPaymentController;
use App\Http\Controllers\Payments\WebhookController;
use App\Http\Controllers\TrainerDirectoryController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

// Public trainer marketplace.
Route::get('/trainers', [TrainerDirectoryController::class, 'index'])->name('trainers.index');
Route::get('/t/{slug}', [TrainerDirectoryController::class, 'show'])->name('trainers.show');

Route::middleware(['auth', 'verified'])->group(function (): void {
    Route::get('/onboarding', [OnboardingController::class, 'show'])->name('onboarding.show');
    Route::post('/onboarding', [OnboardingController::class, 'store'])->name('onboarding.store');
});

// Public, tokenised invoice + payment pages (no auth).
Route::middleware('throttle:public-invoice')->group(function (): void {
    Route::get('/i/{token}', [PublicInvoiceController::class, 'show'])->name('public-invoice.show');
    Route::post('/i/{token}/pay/{gateway}', [PublicPaymentController::class, 'start'])->name('public-invoice.pay');
    Route::get('/i/{token}/return/{payment}', [PublicPaymentController::class, 'return'])->name('public-invoice.return');
    Route::post('/i/{token}/upi', [PublicPaymentController::class, 'submitUpi'])->name('public-invoice.upi');
});

// Gateway webhooks (CSRF-exempt, signature verified in the controller).
Route::middleware('throttle:webhooks')->group(function (): void {
    Route::post('/webhooks/stripe', [WebhookController::class, 'stripe'])->name('webhooks.stripe');
    Route::post('/webhooks/paypal', [WebhookController::class, 'paypal'])->name('webhooks.paypal');
});

require __DIR__.'/trainer.php';
require __DIR__.'/portal.php';
require __DIR__.'/auth.php';
