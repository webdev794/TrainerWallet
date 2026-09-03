<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('packages', function (Blueprint $table): void {
            $table->id();
            $table->foreignIdFor(User::class, 'trainer_id')->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->string('type')->default('session');
            $table->decimal('amount', 10, 2);
            $table->unsignedInteger('sessions_count')->nullable();
            $table->string('billing_interval')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['trainer_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('packages');
    }
};
