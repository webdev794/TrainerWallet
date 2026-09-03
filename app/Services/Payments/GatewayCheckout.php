<?php

namespace App\Services\Payments;

final class GatewayCheckout
{
    /**
     * @param  array<string, mixed>  $meta
     */
    public function __construct(
        public string $redirectUrl,
        public string $gatewayOrderId,
        public array $meta = [],
    ) {}
}
