<?php

namespace App\Services\Payments;

use App\Enums\PaymentGatewayType;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\TrainerProfile;
use Illuminate\Http\Request;
use RuntimeException;

/**
 * UPI is collected against the trainer's own VPA. There is no third-party
 * processor: the payer scans a `upi://pay` QR, then submits the UPI reference
 * number, and the trainer confirms receipt from their dashboard.
 */
class ManualUpiGateway implements PaymentGateway
{
    public function type(): PaymentGatewayType
    {
        return PaymentGatewayType::UpiManual;
    }

    public function isConfiguredFor(TrainerProfile $profile): bool
    {
        return $profile->upi_vpa !== null && $profile->upi_vpa !== '';
    }

    public function createCheckout(Invoice $invoice, Payment $payment, string $successUrl, string $cancelUrl): GatewayCheckout
    {
        // The public invoice page renders the QR and the reference form itself.
        return new GatewayCheckout(
            redirectUrl: $successUrl,
            gatewayOrderId: 'upi_'.$payment->id,
        );
    }

    public function confirmReturn(Payment $payment): GatewayWebhookResult
    {
        // Nothing to confirm automatically — settlement is manual.
        return new GatewayWebhookResult(
            eventId: 'upi_noop_'.$payment->id,
            type: 'upi.manual',
            outcome: GatewayWebhookResult::OUTCOME_IGNORED,
        );
    }

    public function refund(Payment $payment): void
    {
        throw new RuntimeException('Refund UPI payments directly from your bank or UPI app.');
    }

    public function parseWebhook(Request $request): ?GatewayWebhookResult
    {
        return null;
    }

    public static function buildIntentUri(string $vpa, string $payeeName, float $amount, string $note): string
    {
        return 'upi://pay?'.http_build_query([
            'pa' => $vpa,
            'pn' => $payeeName,
            'am' => number_format($amount, 2, '.', ''),
            'cu' => 'INR',
            'tn' => $note,
        ]);
    }
}
