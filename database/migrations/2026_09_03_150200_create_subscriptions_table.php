<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table): void {
            $table->id();
            $table->foreignIdFor(User::class, 'trainer_id')->constrained('users')->cascadeOnDelete();
            $table->string('gateway')->default('stripe');
            $table->string('gateway_customer_id')->nullable();
            $table->string('gateway_subscription_id')->nullable()->index();
            $table->string('plan')->default('pro');
            $table->string('status')->default('incomplete');
            $table->timestamp('current_period_end')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
