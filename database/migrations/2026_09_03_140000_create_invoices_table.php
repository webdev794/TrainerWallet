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
        Schema::create('invoices', function (Blueprint $table): void {
            $table->id();
            $table->foreignIdFor(User::class, 'trainer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignIdFor(Client::class)->constrained()->cascadeOnDelete();
            $table->string('number')->index();
            $table->string('status')->default('draft');
            $table->string('currency', 3)->default('INR');
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('tax_total', 12, 2)->default(0);
            $table->decimal('discount_total', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->decimal('amount_paid', 12, 2)->default(0);
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->timestamp('issued_at')->nullable();
            $table->date('due_date')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('viewed_at')->nullable();
            $table->text('notes')->nullable();
            $table->string('pdf_path')->nullable();
            $table->string('public_token', 64)->unique();
            $table->json('allowed_methods')->nullable();
            $table->unsignedBigInteger('recurring_invoice_id')->nullable()->index();
            $table->timestamps();

            $table->unique(['trainer_id', 'number']);
            $table->index(['trainer_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
