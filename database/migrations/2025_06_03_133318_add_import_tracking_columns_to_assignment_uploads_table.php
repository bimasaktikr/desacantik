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
        Schema::table('assignment_uploads', function (Blueprint $table) {
            $table->enum('import_status', ['pending', 'processing', 'berhasil', 'gagal'])->default('pending')->after('file_path');
            $table->text('error_message')->nullable()->after('import_status');
            $table->timestamp('imported_at')->nullable()->after('error_message');
            $table->integer('total_rows')->default(0)->after('imported_at');
            $table->integer('processed_rows')->default(0)->after('total_rows');
            $table->integer('success_rows')->default(0)->after('processed_rows');
            $table->integer('failed_rows')->default(0)->after('success_rows');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('assignment_uploads', function (Blueprint $table) {
            $table->dropColumn([
                'import_status',
                'error_message',
                'imported_at',
                'total_rows',
                'processed_rows',
                'success_rows',
                'failed_rows'
            ]);
        });
    }
};
