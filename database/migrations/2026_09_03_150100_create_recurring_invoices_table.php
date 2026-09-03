<?php

use App\Models\Client;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recurring_invoices', function (Blueprint $table): void {
            $table->id();
            $table->foreignIdFor(User::class, 'trainer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignIdFor(Client::class)->constrained()->cascadeOnDelete();
            $table->json('template');
            $table->string('interval')->default('month');
            $table->unsignedTinyInteger('due_days')->default(7);
            $table->boolean('auto_send')->default(true);
            $table->date('next_run_at');
            $table->date('last_generated_at')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();

            $table->index(['status', 'next_run_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recurring_invoices');
    }
};
