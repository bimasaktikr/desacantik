<?php

namespace App\Jobs;

use App\Imports\KopindagBusinessImport;
use App\Models\ImportLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

class KopindagBusinessImportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $filePath;
    protected $userId;
    protected $importLogId;

    public function __construct(string $filePath, ?int $userId = null)
    {
        $this->filePath = $filePath;
        $this->userId = $userId;

        // Catat log awal di DB
        $log = ImportLog::create([
            'file_path' => $filePath,
            'status' => 'pending',
            'user_id' => $userId,
        ]);

        $this->importLogId = $log->id;
    }

    public function handle()
    {
        $fullPath = storage_path('app/public/' . $this->filePath);
        Log::info('Starting import job', ['file' => $this->filePath]);

        try {
            if (!file_exists($fullPath) || !is_readable($fullPath)) {
                throw new \Exception('File not found or unreadable.');
            }
            Log::info('Start Process Import');
            Excel::import(new KopindagBusinessImport, $fullPath);

            $message = 'Import success: ' . $this->filePath;
            ImportLog::find($this->importLogId)?->update([
                'status' => 'success',
                'message' => $message,
            ]);
        } catch (Throwable $e) {
            $message = 'Import failed: ' . $e->getMessage();
            ImportLog::find($this->importLogId)?->update([
                'status' => 'failed',
                'message' => $message,
            ]);
            Log::error('Import failed', ['error' => $e->getMessage()]);
        }

        Log::info('Import job completed', ['message' => $message]);
        Storage::disk('public')->put('kopindag-imports/last-import-summary.txt', $message);
    }
}

