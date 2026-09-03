<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $trainer_id
 * @property string $gateway
 * @property string|null $gateway_customer_id
 * @property string|null $gateway_subscription_id
 * @property string $plan
 * @property string $status
 * @property CarbonImmutable|null $current_period_end
 */
class Subscription extends Model
{
    protected $guarded = ['id'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'current_period_end' => 'datetime',
        ];
    }

    public function isActive(): bool
    {
        return in_array($this->status, ['active', 'trialing'], true);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function trainer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'trainer_id');
    }
}
