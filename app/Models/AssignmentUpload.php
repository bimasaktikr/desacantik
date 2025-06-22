<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssignmentUpload extends Model
{
    //

    // add fillable
    protected $fillable = [
        'assignment_id',
        'user_id',
        'file_path',
        'import_status',
        'error_message',
        'imported_at',
        'total_rows',
        'processed_rows',
        'success_rows',
        'failed_rows'
    ];
    // add guaded
    protected $guarded = ['id'];
    // add hidden
    protected $hidden = ['created_at', 'updated_at'];

    protected $casts = [
        'imported_at' => 'datetime',
        'total_rows' => 'integer',
        'processed_rows' => 'integer',
        'success_rows' => 'integer',
        'failed_rows' => 'integer'
    ];

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(Assignment::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function updateProgress(int $processed, int $success, int $failed, array $failures = []): void
    {
        $this->processed_rows = $processed;
        $this->success_rows = $success;
        $this->failed_rows = $failed;

        if (!empty($failures)) {
            $this->error_message = implode("\n", $failures);
        }

        $this->save();
    }

    public function markAsCompleted(): void
    {
        $this->import_status = 'berhasil';
        $this->imported_at = now();

        if ($this->failed_rows > 0) {
            $this->import_status = 'gagal';
        }

        $this->save();
    }

    public function markAsFailed(string $error): void
    {
        $this->update([
            'import_status' => 'gagal',
            'error_message' => $error
        ]);
    }
}
