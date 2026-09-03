<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RuntimeException;
use Stripe\StripeClient;
use Throwable;

class StripeConnectController extends Controller
{
    public function connect(Request $request): RedirectResponse
    {
        $profile = $request->user()->trainerProfile()->firstOrFail();
        $secret = config('services.stripe.secret');

        if (! is_string($secret) || $secret === '') {
            return back()->with('error', 'Add your Stripe API keys to the environment first.');
        }

        try {
            $client = new StripeClient($secret);

            if ($profile->stripe_connect_id === null) {
                $account = $client->accounts->create([
                    'type' => 'express',
                    'email' => $request->user()->email,
                    'capabilities' => [
                        'transfers' => ['requested' => true],
                        'card_payments' => ['requested' => true],
                    ],
                ]);

                $profile->update([
                    'stripe_connect_id' => $account->id,
                    'stripe_connect_status' => 'pending',
                ]);
            }

            $link = $client->accountLinks->create([
                'account' => $profile->stripe_connect_id,
                'refresh_url' => route('settings.stripe.return'),
                'return_url' => route('settings.stripe.return'),
                'type' => 'account_onboarding',
            ]);
        } catch (Throwable $e) {
            report($e);

            return back()->with('error', 'Could not start Stripe onboarding: '.$e->getMessage());
        }

        return redirect()->away($link->url);
    }

    public function return(Request $request): RedirectResponse
    {
        $profile = $request->user()->trainerProfile()->firstOrFail();
        $secret = config('services.stripe.secret');

        if (is_string($secret) && $secret !== '' && $profile->stripe_connect_id !== null) {
            try {
                $account = (new StripeClient($secret))->accounts->retrieve($profile->stripe_connect_id);
                $profile->update([
                    'stripe_connect_status' => $account->charges_enabled ? 'active' : 'pending',
                ]);
            } catch (RuntimeException $e) {
                report($e);
            }
        }

        return redirect()->route('settings.edit')->with('status', 'Stripe connection updated.');
    }
}
