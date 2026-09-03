<?php

namespace App\Services\Payments;

use App\Enums\PaymentGatewayType;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\TrainerProfile;
use Illuminate\Http\Request;
use RuntimeException;
use Stripe\StripeClient;
use Stripe\Webhook;

class StripeGateway implements PaymentGateway
{
    public function type(): PaymentGatewayType
    {
        return PaymentGatewayType::Stripe;
    }

    public function isConfiguredFor(TrainerProfile $profile): bool
    {
        return $this->secret() !== null;
    }

    public function createCheckout(Invoice $invoice, Payment $payment, string $successUrl, string $cancelUrl): GatewayCheckout
    {
        $client = $this->client();
        $amountMinor = Money::toMinor($invoice->outstanding());

        $params = [
            'mode' => 'payment',
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
            'client_reference_id' => $invoice->public_token,
            'metadata' => [
                'invoice_id' => (string) $invoice->id,
                'payment_id' => (string) $payment->id,
            ],
            'line_items' => [[
                'quantity' => 1,
                'price_data' => [
                    'currency' => strtolower($invoice->currency),
                    'unit_amount' => $amountMinor,
                    'product_data' => [
                        'name' => "Invoice {$invoice->number}",
                    ],
                ],
            ]],
        ];

        $connectId = $invoice->trainer->trainerProfile?->stripe_connect_id;
        $feeMinor = $this->platformFeeMinor($amountMinor);

        if ($connectId !== null) {
            $params['payment_intent_data'] = [
                'transfer_data' => ['destination' => $connectId],
            ];

            if ($feeMinor > 0) {
                $params['payment_intent_data']['application_fee_amount'] = $feeMinor;
            }
        }

        $session = $client->checkout->sessions->create($params);

        return new GatewayCheckout(
            redirectUrl: (string) $session->url,
            gatewayOrderId: (string) $session->id,
        );
    }

    public function confirmReturn(Payment $payment): GatewayWebhookResult
    {
        $session = $this->client()->checkout->sessions->retrieve($payment->gateway_order_id, []);

        $outcome = $session->payment_status === 'paid'
            ? GatewayWebhookResult::OUTCOME_PAID
            : GatewayWebhookResult::OUTCOME_IGNORED;

        return new GatewayWebhookResult(
            eventId: 'return_'.$session->id,
            type: 'checkout.session.return',
            outcome: $outcome,
            gatewayOrderId: (string) $session->id,
            gatewayPaymentId: $session->payment_intent ? (string) $session->payment_intent : null,
            amountMinor: $session->amount_total ? (int) $session->amount_total : null,
            payload: $session->toArray(),
        );
    }

    public function refund(Payment $payment): void
    {
        if ($payment->gateway_payment_id === null) {
            throw new RuntimeException('Payment has no Stripe payment intent to refund.');
        }

        $this->client()->refunds->create([
            'payment_intent' => $payment->gateway_payment_id,
        ]);
    }

    public function parseWebhook(Request $request): ?GatewayWebhookResult
    {
        $secret = config('services.stripe.webhook_secret');

        if ($secret) {
            $event = Webhook::constructEvent(
                $request->getContent(),
                $request->header('Stripe-Signature', ''),
                $secret,
            );
        } else {
            $event = (object) $request->json()->all();
        }

        $object = $event->data->object ?? [];
        $object = is_array($object) ? $object : (array) $object;

        return match ($event->type ?? '') {
            'checkout.session.completed' => new GatewayWebhookResult(
                eventId: (string) $event->id,
                type: (string) $event->type,
                outcome: ($object['payment_status'] ?? null) === 'paid'
                    ? GatewayWebhookResult::OUTCOME_PAID
                    : GatewayWebhookResult::OUTCOME_IGNORED,
                gatewayOrderId: $object['id'] ?? null,
                gatewayPaymentId: $object['payment_intent'] ?? null,
                amountMinor: isset($object['amount_total']) ? (int) $object['amount_total'] : null,
                payload: $object,
            ),
            'charge.refunded' => new GatewayWebhookResult(
                eventId: (string) $event->id,
                type: (string) $event->type,
                outcome: GatewayWebhookResult::OUTCOME_REFUNDED,
                gatewayOrderId: null,
                gatewayPaymentId: $object['payment_intent'] ?? null,
                payload: $object,
            ),
            default => new GatewayWebhookResult(
                eventId: (string) ($event->id ?? uniqid('evt_')),
                type: (string) ($event->type ?? 'unknown'),
                outcome: GatewayWebhookResult::OUTCOME_IGNORED,
                payload: $object,
            ),
        };
    }

    private function platformFeeMinor(int $amountMinor): int
    {
        $percent = (float) config('coachpay.platform_fee_percent', 0);

        return (int) round($amountMinor * $percent / 100);
    }

    private function client(): StripeClient
    {
        $secret = $this->secret();

        if ($secret === null) {
            throw new RuntimeException('Stripe is not configured. Add STRIPE_SECRET to your environment.');
        }

        return new StripeClient($secret);
    }

    private function secret(): ?string
    {
        $secret = config('services.stripe.secret');

        return is_string($secret) && $secret !== '' ? $secret : null;
    }
}
