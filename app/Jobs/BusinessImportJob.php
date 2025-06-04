<?php

namespace App\Jobs;

use App\Models\AssignmentUpload;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\BusinessesImport;

class BusinessImportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $upload;
    public $tries = 1;
    public $timeout = 3600; // 1 hour timeout

    public function __construct(AssignmentUpload $upload)
    {
        $this->upload = $upload;
    }

    public function handle()
    {
        try {
            if (!$this->upload) {
                throw new \Exception('Upload record not found');
            }

            Log::info('Starting BusinessImportJob', [
                'upload_id' => $this->upload->id,
                'file_path' => $this->upload->file_path
            ]);

            // Update status to processing
            $this->upload->update(['import_status' => 'processing']);

            // Get the file path
            $filePath = Storage::disk('public')->path($this->upload->file_path);

            if (!file_exists($filePath)) {
                throw new \Exception("File tidak ditemukan di path: {$filePath}");
            }

            // Count total rows first
            $totalRows = Excel::toArray(new BusinessesImport($this->upload->assignment_id, $this->upload), $filePath)[0];
            $this->upload->update(['total_rows' => count($totalRows) - 1]); // Subtract header row

            Log::info('Starting import process', [
                'total_rows' => $this->upload->total_rows
            ]);

            // Import using BusinessesImport class
            $import = new BusinessesImport($this->upload->assignment_id, $this->upload);
            Excel::import($import, $filePath);

            Log::info('Import completed successfully');

            // Mark as completed
            $this->upload->markAsCompleted();

        } catch (\Exception $e) {
            Log::error('Error in BusinessImportJob: ' . $e->getMessage(), [
                'upload_id' => $this->upload?->id,
                'file_path' => $this->upload?->file_path,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            if ($this->upload) {
                // Mark as failed
                $this->upload->markAsFailed($e->getMessage());
            }

            throw $e;
        }
    }

    public function failed(\Throwable $exception)
    {
        Log::error('Job failed: ' . $exception->getMessage(), [
            'upload_id' => $this->upload?->id,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);

        if ($this->upload) {
            $this->upload->markAsFailed($exception->getMessage());
        }
    }
}
