<?php

namespace App\Services;

use App\Models\Subscription;
use App\Models\User;
use App\Models\WebhookEvent;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use RuntimeException;
use Stripe\StripeClient;

class BillingService
{
    public function isConfigured(): bool
    {
        return $this->secret() !== null && is_string(config('services.stripe.price_id')) && config('services.stripe.price_id') !== '';
    }

    /**
     * Create a Stripe Checkout session for the Pro subscription and return its URL.
     */
    public function checkoutUrl(User $trainer, string $successUrl, string $cancelUrl): string
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('Billing is not configured. Set STRIPE_SECRET and STRIPE_PRICE_ID.');
        }

        $session = $this->client()->checkout->sessions->create([
            'mode' => 'subscription',
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
            'customer_email' => $trainer->email,
            'client_reference_id' => (string) $trainer->id,
            'line_items' => [[
                'price' => config('services.stripe.price_id'),
                'quantity' => 1,
            ]],
            'metadata' => ['trainer_id' => (string) $trainer->id],
        ]);

        return (string) $session->url;
    }

    public function billingPortalUrl(User $trainer, string $returnUrl): ?string
    {
        $subscription = $trainer->subscriptions()->latest()->first();

        if ($subscription?->gateway_customer_id === null || ! $this->isConfigured()) {
            return null;
        }

        $session = $this->client()->billingPortal->sessions->create([
            'customer' => $subscription->gateway_customer_id,
            'return_url' => $returnUrl,
        ]);

        return (string) $session->url;
    }

    /**
     * Handle Stripe billing webhooks (subscription lifecycle) idempotently.
     */
    public function handleWebhook(Request $request): void
    {
        $body = $request->json()->all();
        $type = (string) ($body['type'] ?? '');
        $eventId = (string) ($body['id'] ?? uniqid('sub_'));
        $object = is_array($body['data']['object'] ?? null) ? $body['data']['object'] : [];

        if (! in_array($type, [
            'checkout.session.completed',
            'customer.subscription.updated',
            'customer.subscription.deleted',
        ], true)) {
            return;
        }

        if ($type === 'checkout.session.completed' && ($object['mode'] ?? null) !== 'subscription') {
            return;
        }

        $event = WebhookEvent::firstOrCreate(
            ['gateway' => 'stripe_billing', 'event_id' => $eventId],
            ['type' => $type, 'payload' => $body],
        );

        if ($event->processed_at !== null) {
            return;
        }

        match ($type) {
            'checkout.session.completed' => $this->activateFromCheckout($object),
            'customer.subscription.updated' => $this->syncSubscription($object),
            'customer.subscription.deleted' => $this->cancelSubscription($object),
        };

        $event->update(['processed_at' => CarbonImmutable::now()]);
    }

    /**
     * @param  array<string, mixed>  $object
     */
    private function activateFromCheckout(array $object): void
    {
        $trainerId = $object['client_reference_id'] ?? ($object['metadata']['trainer_id'] ?? null);
        $trainer = is_scalar($trainerId) ? User::find($trainerId) : null;

        if ($trainer === null) {
            return;
        }

        $trainer->subscriptions()->updateOrCreate(
            ['gateway_subscription_id' => $object['subscription'] ?? null],
            [
                'gateway' => 'stripe',
                'gateway_customer_id' => $object['customer'] ?? null,
                'plan' => 'pro',
                'status' => 'active',
            ],
        );

        $trainer->trainerProfile?->update(['plan' => 'pro', 'plan_renews_at' => CarbonImmutable::now()->addMonth()]);
    }

    /**
     * @param  array<string, mixed>  $object
     */
    private function syncSubscription(array $object): void
    {
        $subscription = Subscription::query()
            ->where('gateway_subscription_id', $object['id'] ?? null)
            ->first();

        if ($subscription === null) {
            return;
        }

        $status = (string) ($object['status'] ?? 'active');
        $subscription->update([
            'status' => $status,
            'current_period_end' => isset($object['current_period_end'])
                ? CarbonImmutable::createFromTimestamp((int) $object['current_period_end'])
                : null,
        ]);

        $subscription->trainer->trainerProfile?->update([
            'plan' => in_array($status, ['active', 'trialing'], true) ? 'pro' : 'free',
        ]);
    }

    /**
     * @param  array<string, mixed>  $object
     */
    private function cancelSubscription(array $object): void
    {
        $subscription = Subscription::query()
            ->where('gateway_subscription_id', $object['id'] ?? null)
            ->first();

        $subscription?->update(['status' => 'canceled']);
        $subscription?->trainer->trainerProfile?->update(['plan' => 'free', 'plan_renews_at' => null]);
    }

    private function client(): StripeClient
    {
        $secret = $this->secret();

        if ($secret === null) {
            throw new RuntimeException('Stripe is not configured.');
        }

        return new StripeClient($secret);
    }

    private function secret(): ?string
    {
        $secret = config('services.stripe.secret');

        return is_string($secret) && $secret !== '' ? $secret : null;
    }
}
