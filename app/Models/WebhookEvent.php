<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $gateway
 * @property string|null $event_id
 * @property string|null $type
 * @property array<string, mixed>|null $payload
 * @property CarbonImmutable|null $processed_at
 */
class WebhookEvent extends Model
{
    protected $guarded = ['id'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'processed_at' => 'datetime',
        ];
    }
}
