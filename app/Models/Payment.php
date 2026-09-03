<?php

namespace App\Models;

use App\Enums\PaymentGatewayType;
use App\Enums\PaymentStatus;
use Carbon\CarbonImmutable;
use Database\Factories\PaymentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $invoice_id
 * @property int $trainer_id
 * @property PaymentGatewayType $gateway
 * @property string|null $gateway_order_id
 * @property string|null $gateway_payment_id
 * @property string $amount
 * @property string $currency
 * @property string $fee_amount
 * @property string $net_amount
 * @property PaymentStatus $status
 * @property string|null $method_detail
 * @property string|null $reference
 * @property CarbonImmutable|null $paid_at
 * @property string $idempotency_key
 * @property array<string, mixed>|null $raw_payload
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Invoice $invoice
 */
class Payment extends Model
{
    /** @use HasFactory<PaymentFactory> */
    use HasFactory;

    protected $guarded = ['id'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'gateway' => PaymentGatewayType::class,
            'status' => PaymentStatus::class,
            'amount' => 'decimal:2',
            'fee_amount' => 'decimal:2',
            'net_amount' => 'decimal:2',
            'paid_at' => 'datetime',
            'raw_payload' => 'array',
        ];
    }

    /**
     * @return BelongsTo<Invoice, $this>
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function trainer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'trainer_id');
    }
}
