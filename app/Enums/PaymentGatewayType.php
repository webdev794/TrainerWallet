<?php

namespace App\Enums;

enum PaymentGatewayType: string
{
    case Stripe = 'stripe';
    case PayPal = 'paypal';
    case UpiManual = 'upi_manual';
    case Cash = 'cash';

    public function label(): string
    {
        return match ($this) {
            self::Stripe => 'Card (Stripe)',
            self::PayPal => 'PayPal',
            self::UpiManual => 'UPI',
            self::Cash => 'Cash',
        };
    }

    public function isOnline(): bool
    {
        return in_array($this, [self::Stripe, self::PayPal], true);
    }
}
