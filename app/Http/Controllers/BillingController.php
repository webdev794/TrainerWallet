<?php

namespace App\Http\Controllers;

use App\Services\BillingService;
use App\Support\Feature;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

class BillingController extends Controller
{
    public function __construct(private readonly BillingService $billing) {}

    public function show(Request $request): Response
    {
        $profile = $request->user()->trainerProfile()->firstOrFail();
        $feature = Feature::for($profile);
        $subscription = $request->user()->subscriptions()->latest()->first();

        return Inertia::render('billing/index', [
            'plan' => $profile->plan,
            'plans' => config('coachpay.plans'),
            'usage' => [
                'invoices_this_month' => $feature->invoicesUsedThisMonth(),
                'invoice_limit' => $feature->invoiceLimit(),
            ],
            'subscription' => $subscription === null ? null : [
                'status' => $subscription->status,
                'current_period_end' => $subscription->current_period_end?->toDateString(),
            ],
            'billingConfigured' => $this->billing->isConfigured(),
            'renewsAt' => $profile->plan_renews_at?->toDateString(),
        ]);
    }

    public function checkout(Request $request): RedirectResponse
    {
        try {
            $url = $this->billing->checkoutUrl(
                $request->user(),
                successUrl: route('billing.show').'?upgraded=1',
                cancelUrl: route('billing.show'),
            );
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->away($url);
    }

    public function portal(Request $request): RedirectResponse
    {
        $url = $this->billing->billingPortalUrl($request->user(), route('billing.show'));

        if ($url === null) {
            return back()->with('error', 'No billing portal is available yet.');
        }

        return redirect()->away($url);
    }
}
