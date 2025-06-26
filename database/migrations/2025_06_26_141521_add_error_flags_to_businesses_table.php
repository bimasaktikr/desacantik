<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->boolean('name_error')->default(false);
            $table->boolean('description_error')->default(false);
            $table->boolean('address_error')->default(false);
            $table->boolean('village_id_error')->default(false);
            $table->boolean('sls_id_error')->default(false);
            $table->boolean('status_bangunan_error')->default(false);
            $table->boolean('business_category_id_error')->default(false);
            $table->boolean('phone_error')->default(false);
            $table->boolean('email_error')->default(false);
            $table->boolean('owner_name_error')->default(false);
            $table->boolean('owner_gender_error')->default(false);
            $table->boolean('owner_age_error')->default(false);
            $table->boolean('online_status_error')->default(false);
            $table->boolean('pembinaan_error')->default(false);
            $table->boolean('catatan_error')->default(false);
            $table->boolean('user_id_error')->default(false);
            $table->boolean('final_flag')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->dropColumn([
                'name_error', 'description_error', 'address_error', 'village_id_error', 'sls_id_error',
                'status_bangunan_error', 'business_category_id_error', 'phone_error', 'email_error',
                'owner_name_error', 'owner_gender_error', 'owner_age_error', 'online_status_error',
                'pembinaan_error', 'catatan_error', 'user_id_error', 'final_flag'
            ]);
        });
    }
};
