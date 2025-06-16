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
            // GIS/GPS Metadata Columns
            $table->text('remarks')->nullable()->after('point_id');
            $table->dateTime('time')->nullable()->after('remarks');
            $table->string('geometry')->nullable()->after('time');
            $table->decimal('gis_x', 15, 8)->nullable()->after('geometry');
            $table->decimal('gis_y', 15, 8)->nullable()->after('gis_x');
            $table->decimal('elevation', 10, 3)->nullable()->after('gis_y');
            $table->decimal('ortho_height', 10, 3)->nullable()->after('elevation');
            $table->decimal('instrument_ht', 10, 3)->nullable()->after('ortho_height');
            $table->string('fix_id')->nullable()->after('instrument_ht');
            $table->decimal('speed', 10, 3)->nullable()->after('fix_id');
            $table->decimal('bearing', 10, 3)->nullable()->after('speed');
            $table->decimal('horizontal_accuracy', 10, 3)->nullable()->after('bearing');
            $table->decimal('vertical_accuracy', 10, 3)->nullable()->after('horizontal_accuracy');
            $table->decimal('pdop', 10, 3)->nullable()->after('vertical_accuracy');
            $table->decimal('hdop', 10, 3)->nullable()->after('pdop');
            $table->decimal('vdop', 10, 3)->nullable()->after('hdop');
            $table->integer('satellites_in_view')->nullable()->after('vdop');
            $table->integer('satellites_in_use')->nullable()->after('satellites_in_view');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->dropColumn([
                'remarks', 'time', 'geometry', 'gis_x', 'gis_y', 'elevation', 'ortho_height',
                'instrument_ht', 'fix_id', 'speed', 'bearing', 'horizontal_accuracy',
                'vertical_accuracy', 'pdop', 'hdop', 'vdop', 'satellites_in_view', 'satellites_in_use'
            ]);
        });
    }
};