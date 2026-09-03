<?php

namespace App\Services\Payments;

final class GatewayWebhookResult
{
    public const OUTCOME_PAID = 'payment_succeeded';

    public const OUTCOME_FAILED = 'payment_failed';

    public const OUTCOME_REFUNDED = 'refunded';

    public const OUTCOME_IGNORED = 'ignored';

    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public string $eventId,
        public string $type,
        public string $outcome,
        public ?string $gatewayOrderId = null,
        public ?string $gatewayPaymentId = null,
        public ?int $amountMinor = null,
        public ?int $feeMinor = null,
        public array $payload = [],
    ) {}

    public function isIgnored(): bool
    {
        return $this->outcome === self::OUTCOME_IGNORED;
    }
}
