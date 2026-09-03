<?php

use App\Models\Client;
use App\Models\Invoice;
use App\Models\User;
use App\Support\Feature;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

test('the billing page renders both plans', function () {
    $trainer = User::factory()->trainer()->create();

    $this->actingAs($trainer)
        ->get(route('billing.show'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('billing/index')
            ->has('plans.free')
            ->has('plans.pro'));
});

test('the free plan blocks a sixth invoice in a month', function () {
    Storage::fake('local');
    Mail::fake();

    $trainer = User::factory()->trainer()->create();
    $trainer->trainerProfile()->update(['plan' => 'free']);
    $client = Client::factory()->for($trainer, 'trainer')->create();

    Invoice::factory()->count(5)->forClient($client)->create();

    $this->actingAs($trainer)
        ->get(route('invoices.create'))
        ->assertRedirect(route('billing.show'));

    $this->actingAs($trainer)
        ->post(route('invoices.store'), [
            'client_id' => $client->id,
            'items' => [['description' => 'x', 'quantity' => 1, 'unit_amount' => 100]],
        ])
        ->assertRedirect(route('billing.show'));

    expect(Invoice::count())->toBe(5);
});

test('the pro plan has no invoice cap', function () {
    $trainer = User::factory()->trainer()->create();
    $trainer->trainerProfile()->update(['plan' => 'pro']);
    $client = Client::factory()->for($trainer, 'trainer')->create();

    Invoice::factory()->count(8)->forClient($client)->create();

    expect(Feature::for($trainer->trainerProfile->fresh())->canCreateInvoice())->toBeTrue();

    $this->actingAs($trainer)->get(route('invoices.create'))->assertOk();
});

test('a subscription checkout webhook upgrades the trainer to pro', function () {
    $trainer = User::factory()->trainer()->create();
    $trainer->trainerProfile()->update(['plan' => 'free']);

    $payload = [
        'id' => 'evt_sub_1',
        'type' => 'checkout.session.completed',
        'data' => ['object' => [
            'id' => 'cs_sub_1',
            'mode' => 'subscription',
            'client_reference_id' => (string) $trainer->id,
            'customer' => 'cus_123',
            'subscription' => 'sub_123',
        ]],
    ];

    $this->postJson(route('webhooks.stripe'), $payload)->assertNoContent();

    expect($trainer->trainerProfile->fresh()->plan)->toBe('pro');
    $this->assertDatabaseHas('subscriptions', [
        'trainer_id' => $trainer->id,
        'gateway_subscription_id' => 'sub_123',
        'status' => 'active',
    ]);
});

test('a subscription deleted webhook drops the trainer back to free', function () {
    $trainer = User::factory()->trainer()->create();
    $trainer->trainerProfile()->update(['plan' => 'pro']);
    $trainer->subscriptions()->create([
        'gateway' => 'stripe',
        'gateway_subscription_id' => 'sub_gone',
        'plan' => 'pro',
        'status' => 'active',
    ]);

    $payload = [
        'id' => 'evt_sub_del',
        'type' => 'customer.subscription.deleted',
        'data' => ['object' => ['id' => 'sub_gone', 'status' => 'canceled']],
    ];

    $this->postJson(route('webhooks.stripe'), $payload)->assertNoContent();

    expect($trainer->trainerProfile->fresh()->plan)->toBe('free');
});
