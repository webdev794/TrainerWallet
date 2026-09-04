<?php

namespace App\Models;

use App\Enums\PackageType;
use Carbon\CarbonImmutable;
use Database\Factories\PackageFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $trainer_id
 * @property string $name
 * @property string|null $description
 * @property PackageType $type
 * @property string $amount
 * @property int|null $sessions_count
 * @property string|null $billing_interval
 * @property int|null $duration_minutes
 * @property bool $is_active
 * @property bool $is_bookable
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read int|null $reviews_count
 * @property-read float|null $reviews_avg_rating
 */
class Package extends Model
{
    /** @use HasFactory<PackageFactory> */
    use HasFactory;

    protected $guarded = ['id'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'type' => PackageType::class,
            'sessions_count' => 'integer',
            'duration_minutes' => 'integer',
            'is_active' => 'boolean',
            'is_bookable' => 'boolean',
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
     * @return HasMany<Review, $this>
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class)->where('is_published', true);
    }
}
