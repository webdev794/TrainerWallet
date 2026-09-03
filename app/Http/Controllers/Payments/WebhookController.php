<?php

namespace App\Http\Controllers\Payments;

use App\Enums\PaymentGatewayType;
use App\Http\Controllers\Controller;
use App\Services\Payments\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Throwable;

class WebhookController extends Controller
{
    public function __construct(private readonly PaymentService $payments) {}

    public function stripe(Request $request): Response
    {
        return $this->handle(PaymentGatewayType::Stripe, $request);
    }

    public function paypal(Request $request): Response
    {
        return $this->handle(PaymentGatewayType::PayPal, $request);
    }

    private function handle(PaymentGatewayType $type, Request $request): Response
    {
        try {
            $this->payments->handleWebhook($type, $request);
        } catch (Throwable $e) {
            report($e);

            return response('Webhook could not be verified.', 400);
        }

        return response('', 204);
    }
}
