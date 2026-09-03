<?php

namespace App\Services\Payments;

use App\Enums\PaymentGatewayType;
use InvalidArgumentException;

class PaymentGatewayManager
{
    public function __construct(
        private readonly StripeGateway $stripe,
        private readonly PayPalGateway $paypal,
        private readonly ManualUpiGateway $upi,
    ) {}

    public function for(PaymentGatewayType $type): PaymentGateway
    {
        return match ($type) {
            PaymentGatewayType::Stripe => $this->stripe,
            PaymentGatewayType::PayPal => $this->paypal,
            PaymentGatewayType::UpiManual => $this->upi,
            PaymentGatewayType::Cash => throw new InvalidArgumentException('Cash payments are recorded manually.'),
        };
    }

    /**
     * Online gateways that are wired up for the given trainer profile.
     *
     * @return array<int, PaymentGatewayType>
     */
    public function availableFor(\App\Models\TrainerProfile $profile): array
    {
        $available = [];

        foreach ([$this->stripe, $this->paypal, $this->upi] as $gateway) {
            if ($gateway->isConfiguredFor($profile)) {
                $available[] = $gateway->type();
            }
        }

        return $available;
    }
}
