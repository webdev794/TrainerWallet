<?php

namespace App\Models;

use App\Enums\InvoiceStatus;
use Carbon\CarbonImmutable;
use Database\Factories\InvoiceFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property int $trainer_id
 * @property int $client_id
 * @property string $number
 * @property InvoiceStatus $status
 * @property string $currency
 * @property string $subtotal
 * @property string $tax_total
 * @property string $discount_total
 * @property string $total
 * @property string $amount_paid
 * @property string $tax_rate
 * @property CarbonImmutable|null $issued_at
 * @property CarbonImmutable|null $due_date
 * @property CarbonImmutable|null $paid_at
 * @property CarbonImmutable|null $viewed_at
 * @property string|null $notes
 * @property string|null $pdf_path
 * @property string $public_token
 * @property array<int, string>|null $allowed_methods
 * @property int|null $recurring_invoice_id
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Client $client
 * @property-read User $trainer
 * @property-read Collection<int, InvoiceItem> $items
 * @property-read Collection<int, Payment> $payments
 * @property-read int|null $items_count
 */
class Invoice extends Model
{
    /** @use HasFactory<InvoiceFactory> */
    use HasFactory;

    protected $guarded = ['id'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => InvoiceStatus::class,
            'subtotal' => 'decimal:2',
            'tax_total' => 'decimal:2',
            'discount_total' => 'decimal:2',
            'total' => 'decimal:2',
            'amount_paid' => 'decimal:2',
            'tax_rate' => 'decimal:2',
            'issued_at' => 'datetime',
            'due_date' => 'date',
            'paid_at' => 'datetime',
            'viewed_at' => 'datetime',
            'allowed_methods' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Invoice $invoice): void {
            $invoice->public_token ??= Str::random(40);
        });
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function trainer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'trainer_id');
    }

    /**
     * @return BelongsTo<Client, $this>
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * @return HasMany<InvoiceItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    /**
     * @return HasMany<Payment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * @return HasMany<TrainingSession, $this>
     */
    public function trainingSessions(): HasMany
    {
        return $this->hasMany(TrainingSession::class);
    }

    /**
     * @return HasMany<Reminder, $this>
     */
    public function reminders(): HasMany
    {
        return $this->hasMany(Reminder::class);
    }

    public function outstanding(): float
    {
        return round((float) $this->total - (float) $this->amount_paid, 2);
    }

    public function isFullyPaid(): bool
    {
        return $this->outstanding() <= 0.0 && (float) $this->total > 0.0;
    }

    public function isEditable(): bool
    {
        return $this->status === InvoiceStatus::Draft;
    }

    public function isPayable(): bool
    {
        return $this->status->isOpen() && $this->outstanding() > 0.0;
    }

    /**
     * @param  Builder<Invoice>  $query
     */
    public function scopeForTrainer(Builder $query, User $trainer): void
    {
        $query->where('trainer_id', $trainer->id);
    }

    /**
     * Recalculate money fields from the current line items and tax rate.
     */
    public function recalculateTotals(): void
    {
        $subtotal = (float) $this->items->sum(fn (InvoiceItem $item): float => (float) $item->amount);
        $discount = (float) $this->discount_total;
        $taxable = max($subtotal - $discount, 0);
        $tax = round($taxable * ((float) $this->tax_rate / 100), 2);

        $this->subtotal = (string) round($subtotal, 2);
        $this->tax_total = (string) $tax;
        $this->total = (string) round($taxable + $tax, 2);
    }
}
