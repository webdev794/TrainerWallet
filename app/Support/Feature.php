<?php

namespace App\Support;

use App\Models\TrainerProfile;
use Carbon\CarbonImmutable;

/**
 * Plan-based feature gating, driven by config('coachpay.plans').
 */
class Feature
{
    private function __construct(private readonly TrainerProfile $profile) {}

    public static function for(TrainerProfile $profile): self
    {
        return new self($profile);
    }

    /**
     * @return array<string, mixed>
     */
    private function plan(): array
    {
        $plans = config('coachpay.plans', []);

        return $plans[$this->profile->plan] ?? $plans['free'] ?? [];
    }

    public function allows(string $key): bool
    {
        return (bool) ($this->plan()[$key] ?? false);
    }

    public function invoiceLimit(): ?int
    {
        $limit = $this->plan()['invoice_limit_per_month'] ?? null;

        return $limit === null ? null : (int) $limit;
    }

    public function invoicesUsedThisMonth(): int
    {
        return $this->profile->user
            ->invoices()
            ->where('created_at', '>=', CarbonImmutable::now()->startOfMonth())
            ->count();
    }

    public function canCreateInvoice(): bool
    {
        $limit = $this->invoiceLimit();

        return $limit === null || $this->invoicesUsedThisMonth() < $limit;
    }
}
