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
            $table->string('point_id')->nullable()->after('id');
            $table->enum('pertokoan', ['ya', 'tidak'])->nullable()->after('pembinaan');
            $table->unique(['point_id', 'user_id', 'village_id'], 'businesses_point_user_village_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->dropUnique('businesses_point_user_village_unique');
            $table->dropColumn('point_id');
            $table->dropColumn('pertokoan');
        });
    }
};
