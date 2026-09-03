<?php

use App\Models\Invoice;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reminders', function (Blueprint $table): void {
            $table->id();
            $table->foreignIdFor(Invoice::class)->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->integer('offset_days');
            $table->string('channel')->default('mail');
            $table->timestamp('scheduled_for');
            $table->timestamp('sent_at')->nullable();
            $table->string('status')->default('pending');
            $table->timestamps();

            $table->index(['status', 'scheduled_for']);
            $table->unique(['invoice_id', 'type', 'offset_days']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reminders');
    }
};
