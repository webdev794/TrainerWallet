<?php

use App\Models\User;
use Illuminate\Http\UploadedFile;

test('a trainer can import clients from a csv', function () {
    $trainer = User::factory()->trainer()->create();

    $csv = "name,email,phone,rate\n"
        ."Priya Sharma,priya@example.com,+919000000001,1000\n"
        ."Rahul Verma,,,1500\n"
        .",skip@example.com,,999\n";

    $file = UploadedFile::fake()->createWithContent('clients.csv', $csv);

    $this->actingAs($trainer)
        ->post(route('clients.import'), ['file' => $file])
        ->assertRedirect();

    $this->assertDatabaseCount('clients', 2);
    $this->assertDatabaseHas('clients', [
        'trainer_id' => $trainer->id,
        'name' => 'Priya Sharma',
        'email' => 'priya@example.com',
        'default_rate' => 1000,
    ]);
    $this->assertDatabaseHas('clients', [
        'name' => 'Rahul Verma',
        'default_rate' => 1500,
    ]);
});
