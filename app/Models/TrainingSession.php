<?php

namespace App\Models;

use App\Enums\SessionStatus;
use Carbon\CarbonImmutable;
use Database\Factories\TrainingSessionFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $trainer_id
 * @property int $client_id
 * @property CarbonImmutable $scheduled_at
 * @property int $duration_minutes
 * @property string $rate
 * @property SessionStatus $status
 * @property string|null $notes
 * @property int|null $invoice_id
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Client $client
 */
class TrainingSession extends Model
{
    /** @use HasFactory<TrainingSessionFactory> */
    use HasFactory;

    protected $guarded = ['id'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'duration_minutes' => 'integer',
            'rate' => 'decimal:2',
            'status' => SessionStatus::class,
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

    /**
     * Sessions that are completed and not yet attached to an invoice.
     *
     * @param  Builder<TrainingSession>  $query
     */
    public function scopeUnbilled(Builder $query): void
    {
        $query->where('status', SessionStatus::Completed->value)->whereNull('invoice_id');
    }
}
