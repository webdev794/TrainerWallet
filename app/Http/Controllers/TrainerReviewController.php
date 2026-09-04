<?php

namespace App\Http\Controllers;

use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class TrainerReviewController extends Controller
{
    public function index(Request $request): Response
    {
        $trainer = $request->user();
        $profile = $trainer->trainerProfile()->firstOrFail();

        $reviews = $trainer->receivedReviews()
            ->where('is_published', true)
            ->with('package:id,name', 'clientUser:id,name')
            ->latest()
            ->get()
            ->map(fn (Review $review): array => [
                'id' => $review->id,
                'service' => $review->package->name,
                'author' => $review->clientUser->name,
                'rating' => $review->rating,
                'body' => $review->body,
                'improvement' => $review->improvement,
                'created_at' => $review->created_at?->toDateString(),
            ]);

        $byService = DB::table('reviews')
            ->join('packages', 'packages.id', '=', 'reviews.package_id')
            ->where('reviews.trainer_id', $trainer->id)
            ->where('reviews.is_published', true)
            ->groupBy('packages.id', 'packages.name')
            ->orderByDesc('avg_rating')
            ->get([
                'packages.name as service',
                DB::raw('AVG(reviews.rating) as avg_rating'),
                DB::raw('COUNT(*) as review_count'),
            ])
            ->map(fn (object $row): array => [
                'service' => (string) $row->service,
                'avg_rating' => round((float) $row->avg_rating, 1),
                'review_count' => (int) $row->review_count,
            ]);

        return Inertia::render('reviews/index', [
            'reviews' => $reviews,
            'byService' => $byService,
            'summary' => [
                'rating_avg' => $profile->rating_avg,
                'rating_count' => $profile->rating_count,
            ],
        ]);
    }
}
