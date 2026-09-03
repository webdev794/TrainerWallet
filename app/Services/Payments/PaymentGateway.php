<?php

namespace App\Services\Payments;

use App\Enums\PaymentGatewayType;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\TrainerProfile;
use Illuminate\Http\Request;

interface PaymentGateway
{
    public function type(): PaymentGatewayType;

    /**
     * Whether this trainer has finished connecting the gateway.
     */
    public function isConfiguredFor(TrainerProfile $profile): bool;

    /**
     * Create a hosted checkout / order and return where to send the payer.
     */
    public function createCheckout(Invoice $invoice, Payment $payment, string $successUrl, string $cancelUrl): GatewayCheckout;

    /**
     * Confirm an order after the payer returns from the gateway (redirect flows).
     */
    public function confirmReturn(Payment $payment): GatewayWebhookResult;

    public function refund(Payment $payment): void;

    public function parseWebhook(Request $request): ?GatewayWebhookResult;
}
