<?php

namespace App\Http\Controllers;

use App\Models\Package;
use App\Models\Review;
use App\Models\TrainerProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class TrainerDirectoryController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->string('search')->trim()->toString();

        $trainers = TrainerProfile::query()
            ->where('is_public', true)
            ->whereNotNull('slug')
            ->when($search !== '', fn ($query) => $query->where(function ($inner) use ($search): void {
                $inner->where('business_name', 'like', "%{$search}%")
                    ->orWhere('headline', 'like', "%{$search}%")
                    ->orWhere('city', 'like', "%{$search}%");
            }))
            ->withCount('bookablePackages')
            ->orderByDesc('rating_avg')
            ->orderByDesc('rating_count')
            ->paginate(12)
            ->withQueryString()
            ->through(fn (TrainerProfile $profile): array => [
                'slug' => $profile->slug,
                'business_name' => $profile->business_name,
                'headline' => $profile->headline,
                'city' => $profile->city,
                'currency' => $profile->currency,
                'rating_avg' => $profile->rating_avg,
                'rating_count' => $profile->rating_count,
                'services_count' => $profile->bookable_packages_count,
                'logo_url' => $profile->logo_path ? Storage::url($profile->logo_path) : null,
            ]);

        return Inertia::render('trainers/index', [
            'trainers' => $trainers,
            'filters' => ['search' => $search],
        ]);
    }

    public function show(Request $request, string $slug): Response
    {
        $profile = TrainerProfile::query()
            ->where('is_public', true)
            ->where('slug', $slug)
            ->with('user:id,name')
            ->firstOrFail();

        $packages = Package::query()
            ->where('trainer_id', $profile->user_id)
            ->where('is_active', true)
            ->where('is_bookable', true)
            ->withAvg('reviews', 'rating')
            ->withCount('reviews')
            ->orderBy('amount')
            ->get()
            ->map(fn (Package $package): array => [
                'id' => $package->id,
                'name' => $package->name,
                'description' => $package->description,
                'type' => $package->type->value,
                'type_label' => $package->type->label(),
                'amount' => $package->amount,
                'duration_minutes' => $package->duration_minutes,
                'sessions_count' => $package->sessions_count,
                'rating' => $package->reviews_avg_rating !== null ? round((float) $package->reviews_avg_rating, 1) : null,
                'reviews_count' => $package->reviews_count,
            ]);

        $reviews = Review::query()
            ->where('trainer_id', $profile->user_id)
            ->where('is_published', true)
            ->with('package:id,name', 'clientUser:id,name')
            ->latest()
            ->limit(20)
            ->get()
            ->map(fn (Review $review): array => [
                'id' => $review->id,
                'rating' => $review->rating,
                'body' => $review->body,
                'service' => $review->package->name,
                'author' => $review->clientUser->name,
                'created_at' => $review->created_at?->toDateString(),
            ]);

        return Inertia::render('trainers/show', [
            'trainer' => [
                'slug' => $profile->slug,
                'business_name' => $profile->business_name,
                'headline' => $profile->headline,
                'bio' => $profile->bio,
                'city' => $profile->city,
                'currency' => $profile->currency,
                'rating_avg' => $profile->rating_avg,
                'rating_count' => $profile->rating_count,
                'logo_url' => $profile->logo_path ? Storage::url($profile->logo_path) : null,
            ],
            'packages' => $packages,
            'reviews' => $reviews,
            'canBook' => $request->user()?->isClient() ?? false,
        ]);
    }
}
