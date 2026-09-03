<?php

namespace App\Http\Controllers\Payments;

use App\Enums\PaymentGatewayType;
use App\Http\Controllers\Controller;
use App\Services\BillingService;
use App\Services\Payments\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Throwable;

class WebhookController extends Controller
{
    public function __construct(
        private readonly PaymentService $payments,
        private readonly BillingService $billing,
    ) {}

    public function stripe(Request $request): Response
    {
        try {
            $this->payments->handleWebhook(PaymentGatewayType::Stripe, $request);
            $this->billing->handleWebhook($request);
        } catch (Throwable $e) {
            report($e);

            return response('Webhook could not be verified.', 400);
        }

        return response('', 204);
    }

    public function paypal(Request $request): Response
    {
        try {
            $this->payments->handleWebhook(PaymentGatewayType::PayPal, $request);
        } catch (Throwable $e) {
            report($e);

            return response('Webhook could not be verified.', 400);
        }

        return response('', 204);
    }
}
