<?php

use App\Models\Client;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;

test('the reports page renders a six-month series', function () {
    $trainer = User::factory()->trainer()->create();
    $client = Client::factory()->for($trainer, 'trainer')->create();
    $invoice = Invoice::factory()->forClient($client)->paid()->create();
    Payment::factory()->succeeded()->create([
        'invoice_id' => $invoice->id,
        'trainer_id' => $trainer->id,
        'amount' => 2000,
        'net_amount' => 1940,
    ]);

    $this->actingAs($trainer)
        ->get(route('reports.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('reports/index')
            ->has('series', 6)
            ->where('totals.collected_ytd', 2000));
});

test('the payments csv export streams a csv', function () {
    $trainer = User::factory()->trainer()->create();
    $client = Client::factory()->for($trainer, 'trainer')->create();
    $invoice = Invoice::factory()->forClient($client)->create();
    Payment::factory()->succeeded()->create([
        'invoice_id' => $invoice->id,
        'trainer_id' => $trainer->id,
    ]);

    $response = $this->actingAs($trainer)->get(route('reports.payments.csv'));

    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('text/csv')
        ->and($response->streamedContent())->toContain('Date,Invoice,Client');
});
