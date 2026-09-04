<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Package;
use App\Models\Review;
use App\Models\TrainerProfile;
use App\Models\User;

class ReviewService
{
    public function upsert(User $clientUser, Package $package, int $rating, ?string $body, ?string $improvement): Review
    {
        $review = Review::updateOrCreate(
            ['package_id' => $package->id, 'client_user_id' => $clientUser->id],
            [
                'trainer_id' => $package->trainer_id,
                'rating' => $rating,
                'body' => $body,
                'improvement' => $improvement,
                'is_published' => true,
            ],
        );

        $this->recompute($package->trainer_id);

        return $review;
    }

    public function delete(Review $review): void
    {
        $trainerId = $review->trainer_id;
        $review->delete();
        $this->recompute($trainerId);
    }

    public function recompute(int $trainerId): void
    {
        $row = Review::query()
            ->where('trainer_id', $trainerId)
            ->where('is_published', true)
            ->selectRaw('AVG(rating) as avg_rating, COUNT(*) as review_count')
            ->first();

        TrainerProfile::query()->where('user_id', $trainerId)->update([
            'rating_avg' => round((float) ($row->avg_rating ?? 0), 2),
            'rating_count' => (int) ($row->review_count ?? 0),
        ]);
    }

    public function hasBooked(User $clientUser, Package $package): bool
    {
        return Booking::query()
            ->where('client_user_id', $clientUser->id)
            ->where('package_id', $package->id)
            ->exists();
    }
}
