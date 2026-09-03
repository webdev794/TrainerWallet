<?php

namespace App\Models;

use App\Enums\ClientStatus;
use App\Enums\UserRole;
use Carbon\CarbonImmutable;
use Database\Factories\ClientFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $trainer_id
 * @property int|null $client_user_id
 * @property string $name
 * @property string|null $email
 * @property string|null $phone
 * @property string|null $default_rate
 * @property string|null $payment_preference
 * @property string|null $notes
 * @property ClientStatus $status
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
class Client extends Model
{
    /** @use HasFactory<ClientFactory> */
    use HasFactory;

    protected $guarded = ['id'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'default_rate' => 'decimal:2',
            'status' => ClientStatus::class,
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
     * @return BelongsTo<User, $this>
     */
    public function clientUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_user_id');
    }

    /**
     * @return HasMany<TrainingSession, $this>
     */
    public function trainingSessions(): HasMany
    {
        return $this->hasMany(TrainingSession::class);
    }

    /**
     * @return HasMany<Invoice, $this>
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * @param  Builder<Client>  $query
     */
    public function scopeForTrainer(Builder $query, User $trainer): void
    {
        $query->where('trainer_id', $trainer->id);
    }

    public function isInvited(): bool
    {
        return $this->client_user_id !== null;
    }

    /**
     * Link this client to an existing portal (client-role) user with the same email.
     */
    public function linkPortalUserByEmail(): void
    {
        if ($this->email === null || $this->client_user_id !== null) {
            return;
        }

        $user = User::query()
            ->where('email', $this->email)
            ->where('role', UserRole::Client->value)
            ->first();

        if ($user !== null) {
            $this->update(['client_user_id' => $user->id]);
        }
    }
}
