<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Package;
use App\Models\Review;
use App\Services\ReviewService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PortalReviewController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        $reviewedPackageIds = $user->reviews()->pluck('package_id')->all();

        $reviewable = Booking::query()
            ->where('client_user_id', $user->id)
            ->whereNotNull('package_id')
            ->whereNotIn('package_id', $reviewedPackageIds)
            ->with('package:id,name', 'trainer:id,name')
            ->get()
            ->unique('package_id')
            ->map(fn (Booking $booking): array => [
                'package_id' => $booking->package_id,
                'service_name' => $booking->service_name,
                'trainer_name' => $booking->trainer->name,
            ])->values();

        $mine = $user->reviews()
            ->with('package:id,name', 'trainer:id,name')
            ->latest()
            ->get()
            ->map(fn (Review $review): array => [
                'id' => $review->id,
                'package_id' => $review->package_id,
                'service_name' => $review->package->name,
                'trainer_name' => $review->trainer->name,
                'rating' => $review->rating,
                'body' => $review->body,
                'improvement' => $review->improvement,
                'created_at' => $review->created_at?->toDateString(),
            ]);

        return Inertia::render('portal/reviews', [
            'reviewable' => $reviewable,
            'reviews' => $mine,
        ]);
    }

    public function store(Request $request, ReviewService $reviews): RedirectResponse
    {
        $data = $request->validate([
            'package_id' => ['required', 'integer'],
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'body' => ['nullable', 'string', 'max:2000'],
            'improvement' => ['nullable', 'string', 'max:2000'],
        ]);

        $package = Package::findOrFail((int) $data['package_id']);

        abort_unless($reviews->hasBooked($request->user(), $package), 403, 'You can only review a service you have booked.');

        $reviews->upsert(
            $request->user(),
            $package,
            $data['rating'],
            $data['body'] ?? null,
            $data['improvement'] ?? null,
        );

        return back()->with('status', 'Thanks for your review!');
    }

    public function update(Request $request, Review $review, ReviewService $reviews): RedirectResponse
    {
        abort_unless($review->client_user_id === $request->user()->id, 403);

        $data = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'body' => ['nullable', 'string', 'max:2000'],
            'improvement' => ['nullable', 'string', 'max:2000'],
        ]);

        $reviews->upsert(
            $request->user(),
            $review->package,
            $data['rating'],
            $data['body'] ?? null,
            $data['improvement'] ?? null,
        );

        return back()->with('status', 'Review updated.');
    }

    public function destroy(Request $request, Review $review, ReviewService $reviews): RedirectResponse
    {
        abort_unless($review->client_user_id === $request->user()->id, 403);

        $reviews->delete($review);

        return back()->with('status', 'Review removed.');
    }
}
