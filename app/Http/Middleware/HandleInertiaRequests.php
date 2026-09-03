<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();
        $profile = $user?->trainerProfile;

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $user === null ? null : [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role->value,
                    'avatar' => $user->avatar_path,
                    'email_verified_at' => $user->email_verified_at,
                ],
                'trainerProfile' => $profile === null ? null : [
                    'id' => $profile->id,
                    'business_name' => $profile->business_name,
                    'currency' => $profile->currency,
                    'plan' => $profile->plan,
                    'onboarded' => $profile->hasOnboarded(),
                    'logo_url' => $profile->logo_path ? Storage::url($profile->logo_path) : null,
                ],
            ],
            'flash' => [
                'success' => $request->session()->get('status'),
                'error' => $request->session()->get('error'),
            ],
        ];
    }
}
