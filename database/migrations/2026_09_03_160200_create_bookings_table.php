<?php

use App\Models\Client;
use App\Models\Invoice;
use App\Models\Package;
use App\Models\TrainingSession;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('client_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignIdFor(User::class, 'trainer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignIdFor(Package::class)->nullable()->constrained()->nullOnDelete();
            $table->foreignIdFor(Client::class)->nullable()->constrained()->nullOnDelete();
            $table->foreignIdFor(Invoice::class)->nullable()->constrained()->nullOnDelete();
            $table->foreignIdFor(TrainingSession::class)->nullable()->constrained()->nullOnDelete();
            $table->string('service_name');
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3)->default('INR');
            $table->dateTime('scheduled_at')->nullable();
            $table->string('status')->default('confirmed');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['trainer_id', 'status']);
            $table->index(['client_user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
