<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('packages', function (Blueprint $table): void {
            $table->text('description')->nullable()->after('name');
            $table->boolean('is_bookable')->default(false)->after('is_active');
            $table->unsignedSmallInteger('duration_minutes')->nullable()->after('billing_interval');
        });
    }

    public function down(): void
    {
        Schema::table('packages', function (Blueprint $table): void {
            $table->dropColumn(['description', 'is_bookable', 'duration_minutes']);
        });
    }
};
