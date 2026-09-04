<?php

namespace App\Models;

use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property UserRole $role
 * @property string|null $phone
 * @property string|null $avatar_path
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read TrainerProfile|null $trainerProfile
 */
#[Fillable(['name', 'email', 'password', 'role', 'phone'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
        ];
    }

    /**
     * @return HasOne<TrainerProfile, $this>
     */
    public function trainerProfile(): HasOne
    {
        return $this->hasOne(TrainerProfile::class);
    }

    /**
     * Clients belonging to this trainer.
     *
     * @return HasMany<Client, $this>
     */
    public function clients(): HasMany
    {
        return $this->hasMany(Client::class, 'trainer_id');
    }

    /**
     * Packages defined by this trainer.
     *
     * @return HasMany<Package, $this>
     */
    public function packages(): HasMany
    {
        return $this->hasMany(Package::class, 'trainer_id');
    }

    /**
     * Training sessions logged by this trainer.
     *
     * @return HasMany<TrainingSession, $this>
     */
    public function trainingSessions(): HasMany
    {
        return $this->hasMany(TrainingSession::class, 'trainer_id');
    }

    /**
     * Invoices raised by this trainer.
     *
     * @return HasMany<Invoice, $this>
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class, 'trainer_id');
    }

    /**
     * Payments received by this trainer.
     *
     * @return HasMany<Payment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'trainer_id');
    }

    /**
     * Recurring invoice schedules owned by this trainer.
     *
     * @return HasMany<RecurringInvoice, $this>
     */
    public function recurringInvoices(): HasMany
    {
        return $this->hasMany(RecurringInvoice::class, 'trainer_id');
    }

    /**
     * @return HasMany<Subscription, $this>
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class, 'trainer_id');
    }

    /**
     * The client records linking this user (as a client) to one or more trainers.
     *
     * @return HasMany<Client, $this>
     */
    public function clientRecords(): HasMany
    {
        return $this->hasMany(Client::class, 'client_user_id');
    }

    /**
     * @return list<int>
     */
    public function clientRecordIds(): array
    {
        return array_values(array_map('intval', $this->clientRecords()->pluck('id')->all()));
    }

    /**
     * Bookings this user (as a client) has made.
     *
     * @return HasMany<Booking, $this>
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class, 'client_user_id');
    }

    /**
     * Bookings received by this trainer.
     *
     * @return HasMany<Booking, $this>
     */
    public function receivedBookings(): HasMany
    {
        return $this->hasMany(Booking::class, 'trainer_id');
    }

    /**
     * Reviews written by this user (as a client).
     *
     * @return HasMany<Review, $this>
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class, 'client_user_id');
    }

    /**
     * Reviews of this trainer's services.
     *
     * @return HasMany<Review, $this>
     */
    public function receivedReviews(): HasMany
    {
        return $this->hasMany(Review::class, 'trainer_id');
    }

    public function isTrainer(): bool
    {
        return $this->role === UserRole::Trainer;
    }

    public function isClient(): bool
    {
        return $this->role === UserRole::Client;
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    /**
     * The path a user should land on after authenticating.
     */
    public function homePath(): string
    {
        return $this->isClient() ? route('portal.index', absolute: false) : route('dashboard', absolute: false);
    }
}
