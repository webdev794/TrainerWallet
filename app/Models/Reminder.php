<?php

namespace App\Models;

use App\Enums\ReminderType;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $invoice_id
 * @property ReminderType $type
 * @property int $offset_days
 * @property string $channel
 * @property CarbonImmutable $scheduled_for
 * @property CarbonImmutable|null $sent_at
 * @property string $status
 * @property-read Invoice $invoice
 */
class Reminder extends Model
{
    protected $guarded = ['id'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => ReminderType::class,
            'offset_days' => 'integer',
            'scheduled_for' => 'datetime',
            'sent_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Invoice, $this>
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }
}
