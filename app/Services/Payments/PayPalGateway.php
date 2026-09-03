<?php

namespace App\Services\Payments;

use App\Enums\PaymentGatewayType;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\TrainerProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class PayPalGateway implements PaymentGateway
{
    public function type(): PaymentGatewayType
    {
        return PaymentGatewayType::PayPal;
    }

    public function isConfiguredFor(TrainerProfile $profile): bool
    {
        return $this->credentials() !== null;
    }

    public function createCheckout(Invoice $invoice, Payment $payment, string $successUrl, string $cancelUrl): GatewayCheckout
    {
        $amount = number_format($invoice->outstanding(), 2, '.', '');

        $purchaseUnit = [
            'reference_id' => $invoice->public_token,
            'amount' => [
                'currency_code' => strtoupper($invoice->currency),
                'value' => $amount,
            ],
            'custom_id' => (string) $payment->id,
            'description' => "Invoice {$invoice->number}",
        ];

        $merchantId = $invoice->trainer->trainerProfile?->paypal_merchant_id;
        $feeValue = $this->platformFee($invoice->outstanding());

        if ($merchantId !== null) {
            $purchaseUnit['payee'] = ['merchant_id' => $merchantId];

            if ($feeValue > 0) {
                $purchaseUnit['payment_instruction'] = [
                    'platform_fees' => [[
                        'amount' => [
                            'currency_code' => strtoupper($invoice->currency),
                            'value' => number_format($feeValue, 2, '.', ''),
                        ],
                    ]],
                ];
            }
        }

        $response = $this->request()->post('/v2/checkout/orders', [
            'intent' => 'CAPTURE',
            'purchase_units' => [$purchaseUnit],
            'application_context' => [
                'return_url' => $successUrl,
                'cancel_url' => $cancelUrl,
                'shipping_preference' => 'NO_SHIPPING',
                'user_action' => 'PAY_NOW',
            ],
        ])->throw()->json();

        $approveUrl = null;

        foreach ((array) ($response['links'] ?? []) as $link) {
            if (is_array($link) && ($link['rel'] ?? null) === 'approve') {
                $approveUrl = $link['href'] ?? null;

                break;
            }
        }

        if (! is_string($approveUrl)) {
            throw new RuntimeException('PayPal did not return an approval link.');
        }

        return new GatewayCheckout(
            redirectUrl: $approveUrl,
            gatewayOrderId: (string) $response['id'],
        );
    }

    public function confirmReturn(Payment $payment): GatewayWebhookResult
    {
        $response = $this->request()
            ->post("/v2/checkout/orders/{$payment->gateway_order_id}/capture")
            ->throw()
            ->json();

        $capture = $response['purchase_units'][0]['payments']['captures'][0] ?? [];
        $paid = ($response['status'] ?? null) === 'COMPLETED';

        return new GatewayWebhookResult(
            eventId: 'return_'.($response['id'] ?? $payment->gateway_order_id),
            type: 'checkout.order.capture',
            outcome: $paid ? GatewayWebhookResult::OUTCOME_PAID : GatewayWebhookResult::OUTCOME_IGNORED,
            gatewayOrderId: (string) ($response['id'] ?? $payment->gateway_order_id),
            gatewayPaymentId: $capture['id'] ?? null,
            amountMinor: isset($capture['amount']['value'])
                ? Money::toMinor($capture['amount']['value'])
                : null,
            payload: $response,
        );
    }

    public function refund(Payment $payment): void
    {
        if ($payment->gateway_payment_id === null) {
            throw new RuntimeException('Payment has no PayPal capture to refund.');
        }

        $this->request()
            ->post("/v2/payments/captures/{$payment->gateway_payment_id}/refund")
            ->throw();
    }

    public function parseWebhook(Request $request): ?GatewayWebhookResult
    {
        $body = $request->json()->all();
        $type = $body['event_type'] ?? '';
        $resource = $body['resource'] ?? [];

        $outcome = match ($type) {
            'PAYMENT.CAPTURE.COMPLETED', 'CHECKOUT.ORDER.APPROVED' => GatewayWebhookResult::OUTCOME_PAID,
            'PAYMENT.CAPTURE.REFUNDED', 'PAYMENT.CAPTURE.REVERSED' => GatewayWebhookResult::OUTCOME_REFUNDED,
            default => GatewayWebhookResult::OUTCOME_IGNORED,
        };

        return new GatewayWebhookResult(
            eventId: (string) ($body['id'] ?? uniqid('WH-')),
            type: (string) $type,
            outcome: $outcome,
            gatewayOrderId: $resource['supplementary_data']['related_ids']['order_id'] ?? ($resource['id'] ?? null),
            gatewayPaymentId: $resource['id'] ?? null,
            amountMinor: isset($resource['amount']['value']) ? Money::toMinor($resource['amount']['value']) : null,
            payload: $body,
        );
    }

    private function platformFee(float $amount): float
    {
        return round($amount * (float) config('coachpay.platform_fee_percent', 0) / 100, 2);
    }

    private function request(): \Illuminate\Http\Client\PendingRequest
    {
        return Http::baseUrl(config('services.paypal.base_url'))
            ->withToken($this->accessToken())
            ->acceptJson()
            ->asJson();
    }

    private function accessToken(): string
    {
        $credentials = $this->credentials();

        if ($credentials === null) {
            throw new RuntimeException('PayPal is not configured. Add PAYPAL_CLIENT_ID and PAYPAL_CLIENT_SECRET.');
        }

        return Cache::remember('paypal.access_token', now()->addMinutes(60), function () use ($credentials): string {
            $response = Http::baseUrl(config('services.paypal.base_url'))
                ->asForm()
                ->withBasicAuth($credentials['id'], $credentials['secret'])
                ->post('/v1/oauth2/token', ['grant_type' => 'client_credentials'])
                ->throw()
                ->json();

            return (string) $response['access_token'];
        });
    }

    /**
     * @return array{id: string, secret: string}|null
     */
    private function credentials(): ?array
    {
        $id = config('services.paypal.client_id');
        $secret = config('services.paypal.client_secret');

        if (is_string($id) && $id !== '' && is_string($secret) && $secret !== '') {
            return ['id' => $id, 'secret' => $secret];
        }

        return null;
    }
}
