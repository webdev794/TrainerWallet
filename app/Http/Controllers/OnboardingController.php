<?php

namespace App\Http\Controllers;

use App\Models\TrainerProfile;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class OnboardingController extends Controller
{
    public function show(Request $request): RedirectResponse|Response
    {
        $user = $request->user();

        if (! $user->isTrainer()) {
            return redirect()->route('portal');
        }

        $profile = $this->resolveProfile($request);

        if ($profile->hasOnboarded()) {
            return redirect()->route('dashboard');
        }

        return Inertia::render('onboarding', [
            'profile' => [
                'business_name' => $profile->business_name,
                'currency' => $profile->currency,
                'upi_vpa' => $profile->upi_vpa,
                'invoice_prefix' => $profile->invoice_prefix,
                'logo_url' => $profile->logo_path ? Storage::url($profile->logo_path) : null,
            ],
            'currencies' => ['INR', 'USD', 'EUR', 'GBP', 'AUD', 'CAD', 'SGD', 'AED'],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->isTrainer(), 403);

        $validated = $request->validate([
            'business_name' => ['required', 'string', 'max:255'],
            'currency' => ['required', 'string', 'size:3'],
            'upi_vpa' => ['nullable', 'string', 'max:255', 'regex:/^[\w.\-]+@[\w.\-]+$/'],
            'invoice_prefix' => ['required', 'string', 'max:12', 'regex:/^[A-Za-z0-9\-]+$/'],
            'logo' => ['nullable', 'image', 'max:2048'],
        ]);

        $profile = $this->resolveProfile($request);
        $profile->fill([
            'business_name' => $validated['business_name'],
            'currency' => strtoupper($validated['currency']),
            'upi_vpa' => $validated['upi_vpa'] ?? null,
            'invoice_prefix' => strtoupper($validated['invoice_prefix']),
        ]);

        if ($request->hasFile('logo')) {
            $path = $request->file('logo')->store('logos', 'public');

            if (is_string($path)) {
                $profile->logo_path = $path;
            }
        }

        if ($profile->onboarded_at === null) {
            $profile->onboarded_at = CarbonImmutable::now();
        }

        $profile->save();

        return redirect()->route('dashboard')->with('status', 'Your workspace is ready.');
    }

    private function resolveProfile(Request $request): TrainerProfile
    {
        return $request->user()->trainerProfile()->firstOrCreate([], [
            'business_name' => $request->user()->name,
            'currency' => config('coachpay.default_currency'),
            'invoice_prefix' => TrainerProfile::defaultPrefixFor($request->user()->name),
        ]);
    }
}
