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
        Schema::create('kopindag_businesses', function (Blueprint $table) {
            $table->id();
            $table->string('uraian_jenis_proyek')->nullable();
            $table->string('nib')->unique()->comment('Nomor Induk Berusaha, harus unik');
            $table->string('nama_perusahaan')->nullable();
            $table->date('tanggal_terbit_oss')->nullable();
            $table->string('status_penanaman_modal')->nullable();
            $table->string('jenis_perusahaan')->nullable();
            $table->string('risiko_proyek')->nullable();
            $table->string('nama_proyek')->nullable();
            $table->string('skala_usaha')->nullable();
            $table->text('alamat_usaha')->nullable();
            $table->string('kabupaten_kota_usaha')->nullable();
            $table->foreignId('district_id')->nullable()->constrained('districts')->nullOnDelete();
            $table->foreignId('village_id')->nullable()->constrained('villages')->nullOnDelete();
            $table->date('tanggal_pengajuan_proyek')->nullable();
            $table->string('kbli', 20)->nullable();
            $table->string('judul_kbli')->nullable();
            $table->string('sektor_pembina')->nullable();
            $table->string('nama_user')->nullable();
            $table->string('email')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kopindag_businesses');
    }
};
