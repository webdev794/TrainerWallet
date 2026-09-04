<?php

namespace App\Http\Controllers\Portal;

use App\Enums\BookingStatus;
use App\Enums\SessionStatus;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Package;
use App\Models\TrainerProfile;
use App\Services\BookingService;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

class PortalBookingController extends Controller
{
    public function index(Request $request): Response
    {
        $bookings = $request->user()->bookings()
            ->with('trainer:id,name', 'invoice:id,number,status,public_token')
            ->latest()
            ->get()
            ->map(fn (Booking $booking): array => [
                'id' => $booking->id,
                'service_name' => $booking->service_name,
                'trainer_name' => $booking->trainer->name,
                'amount' => $booking->amount,
                'currency' => $booking->currency,
                'scheduled_at' => $booking->scheduled_at?->toIso8601String(),
                'status' => $booking->status->value,
                'status_label' => $booking->status->label(),
                'invoice_number' => $booking->invoice?->number,
                'invoice_status' => $booking->invoice?->status->value,
                'invoice_token' => $booking->invoice?->public_token,
            ]);

        return Inertia::render('portal/bookings', ['bookings' => $bookings]);
    }

    public function create(Request $request): Response
    {
        $trainers = TrainerProfile::query()
            ->where('is_public', true)
            ->whereNotNull('slug')
            ->orderBy('business_name')
            ->get()
            ->map(fn (TrainerProfile $profile): array => [
                'slug' => $profile->slug,
                'business_name' => $profile->business_name,
                'currency' => $profile->currency,
                'services' => Package::query()
                    ->where('trainer_id', $profile->user_id)
                    ->where('is_active', true)
                    ->where('is_bookable', true)
                    ->orderBy('amount')
                    ->get()
                    ->map(fn (Package $package): array => [
                        'id' => $package->id,
                        'name' => $package->name,
                        'type' => $package->type->value,
                        'amount' => $package->amount,
                        'duration_minutes' => $package->duration_minutes,
                        'needs_slot' => $package->type->value === 'session',
                    ])->values()->all(),
            ])
            ->filter(fn (array $t): bool => count($t['services']) > 0)
            ->values();

        return Inertia::render('portal/book', [
            'trainers' => $trainers,
            'prefill' => [
                'trainer' => $request->string('trainer')->toString() ?: null,
                'package' => $request->integer('package') ?: null,
            ],
        ]);
    }

    public function store(Request $request, BookingService $bookings): RedirectResponse
    {
        $data = $request->validate([
            'package_id' => ['required', Rule::exists('packages', 'id')->where('is_bookable', true)->where('is_active', true)],
            'scheduled_at' => ['nullable', 'date', 'after:now'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $package = Package::findOrFail((int) $data['package_id']);

        if ($package->type->value === 'session' && empty($data['scheduled_at'])) {
            throw ValidationException::withMessages([
                'scheduled_at' => 'Choose a date and time for this session.',
            ]);
        }

        try {
            $booking = $bookings->book(
                $request->user(),
                $package,
                isset($data['scheduled_at']) ? CarbonImmutable::parse($data['scheduled_at']) : null,
                $data['notes'] ?? null,
            );
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('public-invoice.show', $booking->invoice->public_token)
            ->with('status', 'Booked! Complete payment below to confirm your spot.');
    }

    public function update(Request $request, Booking $booking): RedirectResponse
    {
        abort_unless($booking->client_user_id === $request->user()->id, 403);

        $request->validate(['status' => ['required', Rule::in(['cancelled'])]]);

        abort_if($booking->status !== BookingStatus::Confirmed, 422, 'This booking can no longer be changed.');

        $booking->update(['status' => BookingStatus::Cancelled]);
        $booking->trainingSession?->update(['status' => SessionStatus::Cancelled]);

        return back()->with('status', 'Booking cancelled. Contact your trainer about the invoice.');
    }
}
