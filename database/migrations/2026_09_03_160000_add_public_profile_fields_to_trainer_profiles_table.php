<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trainer_profiles', function (Blueprint $table): void {
            $table->boolean('is_public')->default(false)->after('onboarded_at');
            $table->string('slug')->nullable()->unique()->after('is_public');
            $table->string('headline')->nullable()->after('slug');
            $table->text('bio')->nullable()->after('headline');
            $table->string('city')->nullable()->after('bio');
            $table->decimal('rating_avg', 3, 2)->default(0)->after('city');
            $table->unsignedInteger('rating_count')->default(0)->after('rating_avg');
        });
    }

    public function down(): void
    {
        Schema::table('trainer_profiles', function (Blueprint $table): void {
            $table->dropColumn(['is_public', 'slug', 'headline', 'bio', 'city', 'rating_avg', 'rating_count']);
        });
    }
};
