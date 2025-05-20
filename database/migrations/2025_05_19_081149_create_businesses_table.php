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
        Schema::create('businesses', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('address');
            $table->foreignId('sls_id')->constrained()->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->enum('status_bangunan',['Tetap', 'Tidak Tetap']);
            $table->foreignId('business_category_id')->constrained()->onDelete('cascade');
            $table->string('description');
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('owner_name')->nullable();
            $table->enum('owner_gender',['Laki-Laki', 'Perempuan'])->nullable();
            $table->integer('owner_age')->nullable();
            $table->enum('online_status',['Ya', 'Tidak'])->nullable();
            $table->enum('pembinaan', ['Ya', 'Tidak'])->default('Tidak')->comment('Apakah bisnis ini ingin dibina?')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('businesses');
    }
};
