<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Enums\ClientStatus;
use App\Enums\InvoiceStatus;
use App\Enums\PackageType;
use App\Enums\PaymentGatewayType;
use App\Enums\SessionStatus;
use App\Models\Booking;
use App\Models\Client;
use App\Models\Package;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class BookingService
{
    public function __construct(
        private readonly InvoiceNumberService $numbers,
        private readonly InvoiceReminderService $reminders,
        private readonly InvoiceDocumentService $documents,
    ) {}

    /**
     * Instant-book a listed service: creates the client link, an optional
     * scheduled session, and a sent invoice, then emails it.
     */
    public function book(User $clientUser, Package $package, ?CarbonImmutable $scheduledAt, ?string $notes): Booking
    {
        $trainer = $package->trainer;

        if ($trainer->trainerProfile === null || ! $package->is_bookable || ! $package->is_active) {
            throw new RuntimeException('This service is not available for booking.');
        }

        if ($package->type === PackageType::Session && $scheduledAt === null) {
            throw new RuntimeException('Please choose a date and time for this session.');
        }

        $booking = DB::transaction(function () use ($clientUser, $package, $trainer, $scheduledAt, $notes): Booking {
            $client = Client::firstOrCreate(
                ['trainer_id' => $trainer->id, 'client_user_id' => $clientUser->id],
                [
                    'name' => $clientUser->name,
                    'email' => $clientUser->email,
                    'status' => ClientStatus::Active,
                    'default_rate' => $package->type === PackageType::Session ? $package->amount : null,
                ],
            );

            $session = null;

            if ($package->type === PackageType::Session && $scheduledAt !== null) {
                $session = $trainer->trainingSessions()->create([
                    'client_id' => $client->id,
                    'scheduled_at' => $scheduledAt,
                    'duration_minutes' => $package->duration_minutes ?? 60,
                    'rate' => $package->amount,
                    'status' => SessionStatus::Scheduled,
                ]);
            }

            $invoice = $trainer->invoices()->create([
                'client_id' => $client->id,
                'number' => $this->numbers->next($trainer->trainerProfile),
                'status' => InvoiceStatus::Sent,
                'currency' => $trainer->trainerProfile->currency,
                'issued_at' => CarbonImmutable::now(),
                'due_date' => CarbonImmutable::now()->addDays(3)->toDateString(),
                'notes' => $notes,
                'allowed_methods' => [
                    PaymentGatewayType::UpiManual->value,
                    PaymentGatewayType::Stripe->value,
                    PaymentGatewayType::PayPal->value,
                ],
            ]);

            $invoice->items()->create([
                'description' => $package->name,
                'quantity' => 1,
                'unit_amount' => $package->amount,
                'amount' => $package->amount,
                'training_session_id' => $session?->id,
            ]);

            $invoice->load('items');
            $invoice->recalculateTotals();
            $invoice->save();

            $session?->update(['invoice_id' => $invoice->id]);
            $this->reminders->schedule($invoice->fresh());

            return Booking::create([
                'client_user_id' => $clientUser->id,
                'trainer_id' => $trainer->id,
                'package_id' => $package->id,
                'client_id' => $client->id,
                'invoice_id' => $invoice->id,
                'training_session_id' => $session?->id,
                'service_name' => $package->name,
                'amount' => $package->amount,
                'currency' => $trainer->trainerProfile->currency,
                'scheduled_at' => $scheduledAt,
                'status' => BookingStatus::Confirmed,
                'notes' => $notes,
            ]);
        });

        $this->documents->emailInvoiceToClient($booking->invoice->fresh());

        return $booking->fresh();
    }
}
