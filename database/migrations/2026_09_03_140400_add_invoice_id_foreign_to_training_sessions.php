<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // SQLite cannot add a foreign key to an existing table; the app enforces this link.
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        Schema::table('training_sessions', function (Blueprint $table): void {
            $table->foreign('invoice_id')->references('id')->on('invoices')->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        Schema::table('training_sessions', function (Blueprint $table): void {
            $table->dropForeign(['invoice_id']);
        });
    }
};
