<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\TrainerProfileFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property int $user_id
 * @property string $business_name
 * @property string|null $logo_path
 * @property string $currency
 * @property string|null $upi_vpa
 * @property string $invoice_prefix
 * @property int $next_invoice_number
 * @property string|null $address
 * @property string|null $tax_id
 * @property string|null $stripe_connect_id
 * @property string|null $stripe_connect_status
 * @property string|null $paypal_merchant_id
 * @property string|null $paypal_onboard_status
 * @property string $plan
 * @property CarbonImmutable|null $plan_renews_at
 * @property CarbonImmutable|null $onboarded_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
class TrainerProfile extends Model
{
    /** @use HasFactory<TrainerProfileFactory> */
    use HasFactory;

    protected $guarded = ['id'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'next_invoice_number' => 'integer',
            'plan_renews_at' => 'datetime',
            'onboarded_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isPro(): bool
    {
        return $this->plan === 'pro';
    }

    public function hasOnboarded(): bool
    {
        return $this->onboarded_at !== null;
    }

    /**
     * Peek at the next formatted invoice number without consuming it.
     */
    public function peekInvoiceNumber(): string
    {
        return $this->invoice_prefix.'-'.str_pad((string) $this->next_invoice_number, 4, '0', STR_PAD_LEFT);
    }

    public static function defaultPrefixFor(string $name): string
    {
        $initials = Str::of($name)
            ->explode(' ')
            ->filter()
            ->map(fn (string $part): string => Str::upper(Str::substr($part, 0, 1)))
            ->take(3)
            ->implode('');

        return $initials !== '' ? $initials : 'INV';
    }
}
