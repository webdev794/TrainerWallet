<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\RecurringInvoiceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $trainer_id
 * @property int $client_id
 * @property array<string, mixed> $template
 * @property string $interval
 * @property int $due_days
 * @property bool $auto_send
 * @property CarbonImmutable $next_run_at
 * @property CarbonImmutable|null $last_generated_at
 * @property string $status
 * @property-read Client $client
 */
class RecurringInvoice extends Model
{
    /** @use HasFactory<RecurringInvoiceFactory> */
    use HasFactory;

    protected $guarded = ['id'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'template' => 'array',
            'due_days' => 'integer',
            'auto_send' => 'boolean',
            'next_run_at' => 'date',
            'last_generated_at' => 'date',
        ];
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

    public function advance(): CarbonImmutable
    {
        return match ($this->interval) {
            'week' => $this->next_run_at->addWeek(),
            'quarter' => $this->next_run_at->addMonths(3),
            'year' => $this->next_run_at->addYear(),
            default => $this->next_run_at->addMonth(),
        };
    }
}
