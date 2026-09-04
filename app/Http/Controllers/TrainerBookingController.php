<?php

namespace App\Http\Controllers;

use App\Enums\BookingStatus;
use App\Enums\SessionStatus;
use App\Models\Booking;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class TrainerBookingController extends Controller
{
    public function index(Request $request): Response
    {
        $bookings = $request->user()->receivedBookings()
            ->with('clientUser:id,name', 'invoice:id,number,status')
            ->latest()
            ->paginate(20)
            ->through(fn (Booking $booking): array => [
                'id' => $booking->id,
                'client_name' => $booking->clientUser->name,
                'service_name' => $booking->service_name,
                'amount' => $booking->amount,
                'currency' => $booking->currency,
                'scheduled_at' => $booking->scheduled_at?->toIso8601String(),
                'status' => $booking->status->value,
                'status_label' => $booking->status->label(),
                'invoice_id' => $booking->invoice_id,
                'invoice_number' => $booking->invoice?->number,
                'invoice_status' => $booking->invoice?->status->value,
                'created_at' => $booking->created_at?->toIso8601String(),
            ]);

        return Inertia::render('bookings/index', [
            'bookings' => $bookings,
            'currency' => $request->user()->trainerProfile()->value('currency'),
        ]);
    }

    public function update(Request $request, Booking $booking): RedirectResponse
    {
        abort_unless($booking->trainer_id === $request->user()->id, 403);

        $data = $request->validate([
            'status' => ['required', Rule::in([BookingStatus::Completed->value, BookingStatus::Cancelled->value])],
        ]);

        $booking->update(['status' => $data['status']]);

        if ($data['status'] === BookingStatus::Completed->value) {
            $booking->trainingSession?->update(['status' => SessionStatus::Completed]);
        } elseif ($data['status'] === BookingStatus::Cancelled->value) {
            $booking->trainingSession?->update(['status' => SessionStatus::Cancelled]);
        }

        return back()->with('status', 'Booking updated.');
    }
}
