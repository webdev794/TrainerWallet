<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trainer_profiles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('business_name');
            $table->string('logo_path')->nullable();
            $table->string('currency', 3)->default('INR');
            $table->string('upi_vpa')->nullable();
            $table->string('invoice_prefix', 12)->default('INV');
            $table->unsignedInteger('next_invoice_number')->default(1);
            $table->string('address')->nullable();
            $table->string('tax_id')->nullable();
            $table->string('stripe_connect_id')->nullable();
            $table->string('stripe_connect_status')->nullable();
            $table->string('paypal_merchant_id')->nullable();
            $table->string('paypal_onboard_status')->nullable();
            $table->string('plan')->default('free');
            $table->timestamp('plan_renews_at')->nullable();
            $table->timestamp('onboarded_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trainer_profiles');
    }
};
