<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First drop the existing unique constraint if it exists
        Schema::table('businesses', function (Blueprint $table) {
            $table->dropUnique(['point_id']);
        });

        // Add the new composite unique constraint
        Schema::table('businesses', function (Blueprint $table) {
            $table->unique(['point_id', 'user_id', 'village_id'], 'businesses_point_user_village_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop the composite unique constraint
        Schema::table('businesses', function (Blueprint $table) {
            $table->dropUnique('businesses_point_user_village_unique');
        });

        // Restore the original unique constraint
        Schema::table('businesses', function (Blueprint $table) {
            $table->unique(['point_id']);
        });
    }
};
