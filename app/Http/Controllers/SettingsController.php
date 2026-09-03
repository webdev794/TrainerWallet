<?php

namespace App\Http\Controllers;

use App\Services\Payments\PaymentGatewayManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    public function __construct(private readonly PaymentGatewayManager $gateways) {}

    public function edit(Request $request): Response
    {
        $profile = $request->user()->trainerProfile()->firstOrFail();

        return Inertia::render('settings/index', [
            'profile' => [
                'business_name' => $profile->business_name,
                'currency' => $profile->currency,
                'upi_vpa' => $profile->upi_vpa,
                'invoice_prefix' => $profile->invoice_prefix,
                'address' => $profile->address,
                'tax_id' => $profile->tax_id,
                'logo_url' => $profile->logo_path ? Storage::url($profile->logo_path) : null,
                'plan' => $profile->plan,
                'stripe_connect_status' => $profile->stripe_connect_status,
                'stripe_connected' => $profile->stripe_connect_id !== null,
                'paypal_merchant_id' => $profile->paypal_merchant_id,
            ],
            'connectedGateways' => array_map(
                fn ($type): string => $type->value,
                $this->gateways->availableFor($profile),
            ),
            'currencies' => ['INR', 'USD', 'EUR', 'GBP', 'AUD', 'CAD', 'SGD', 'AED'],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $profile = $request->user()->trainerProfile()->firstOrFail();

        $validated = $request->validate([
            'business_name' => ['required', 'string', 'max:255'],
            'currency' => ['required', 'string', 'size:3'],
            'upi_vpa' => ['nullable', 'string', 'max:255', 'regex:/^[\w.\-]+@[\w.\-]+$/'],
            'invoice_prefix' => ['required', 'string', 'max:12', 'regex:/^[A-Za-z0-9\-]+$/'],
            'address' => ['nullable', 'string', 'max:500'],
            'tax_id' => ['nullable', 'string', 'max:100'],
            'paypal_merchant_id' => ['nullable', 'string', 'max:100'],
            'logo' => ['nullable', 'image', 'max:2048'],
        ]);

        $profile->fill([
            'business_name' => $validated['business_name'],
            'currency' => strtoupper($validated['currency']),
            'upi_vpa' => $validated['upi_vpa'] ?? null,
            'invoice_prefix' => strtoupper($validated['invoice_prefix']),
            'address' => $validated['address'] ?? null,
            'tax_id' => $validated['tax_id'] ?? null,
            'paypal_merchant_id' => $validated['paypal_merchant_id'] ?? null,
        ]);

        if ($request->hasFile('logo')) {
            $path = $request->file('logo')->store('logos', 'public');

            if (is_string($path)) {
                $profile->logo_path = $path;
            }
        }

        $profile->save();

        return back()->with('status', 'Settings saved.');
    }
}
