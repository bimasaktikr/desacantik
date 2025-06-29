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
        Schema::table('businesses', function (Blueprint $table) {
            // Update owner_gender enum to include '-'
            $table->enum('owner_gender', ['-', 'Laki-Laki', 'Perempuan'])->nullable()->change();

            // Update online_status enum to include '-'
            $table->enum('online_status', ['-', 'Ya', 'Tidak'])->nullable()->change();

            // Update pembinaan enum to include '-'
            $table->enum('pembinaan', ['-', 'Ya', 'Tidak'])->default('Tidak')->comment('Apakah bisnis ini ingin dibina?')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            // Revert owner_gender enum
            $table->enum('owner_gender', ['Laki-Laki', 'Perempuan'])->nullable()->change();

            // Revert online_status enum
            $table->enum('online_status', ['Ya', 'Tidak'])->nullable()->change();

            // Revert pembinaan enum
            $table->enum('pembinaan', ['Ya', 'Tidak'])->default('Tidak')->comment('Apakah bisnis ini ingin dibina?')->nullable()->change();
        });
    }
};