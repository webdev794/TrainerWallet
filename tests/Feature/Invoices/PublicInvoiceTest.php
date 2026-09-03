<?php

use App\Enums\InvoiceStatus;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\User;

test('a draft invoice is not publicly visible', function () {
    $invoice = Invoice::factory()->create(['status' => InvoiceStatus::Draft]);

    $this->get(route('public-invoice.show', $invoice->public_token))->assertNotFound();
});

test('opening a sent invoice marks it viewed', function () {
    $trainer = User::factory()->trainer()->create();
    $client = Client::factory()->for($trainer, 'trainer')->create();
    $invoice = Invoice::factory()->forClient($client)->sent()->create();
    $invoice->items()->create(['description' => 'x', 'quantity' => 1, 'unit_amount' => 1000, 'amount' => 1000]);

    $this->get(route('public-invoice.show', $invoice->public_token))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('invoice/public'));

    expect($invoice->fresh()->status)->toBe(InvoiceStatus::Viewed);
});

test('an unknown token 404s', function () {
    $this->get(route('public-invoice.show', 'nope-nope-nope'))->assertNotFound();
});

test('a client can view their own invoice in the portal but not another clients', function () {
    $trainerUser = User::factory()->trainer()->create();
    $clientUser = User::factory()->client()->create();
    $client = Client::factory()->for($trainerUser, 'trainer')->create(['client_user_id' => $clientUser->id]);

    $mine = Invoice::factory()->forClient($client)->sent()->create();
    $theirs = Invoice::factory()->sent()->create();

    $this->actingAs($clientUser)->get(route('portal.invoices.show', $mine))->assertOk();
    $this->actingAs($clientUser)->get(route('portal.invoices.show', $theirs))->assertForbidden();
});

test('the public pay endpoint rejects an unknown gateway', function () {
    $invoice = Invoice::factory()->sent()->create();

    $this->post(route('public-invoice.pay', ['token' => $invoice->public_token, 'gateway' => 'bitcoin']))
        ->assertNotFound();
});
