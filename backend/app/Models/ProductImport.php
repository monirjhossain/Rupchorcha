<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Webkul\User\Models\Admin;

class ProductImport extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'file_name',
        'file_path',
        'status',
        'total_rows',
        'processed_rows',
        'success_count',
        'failed_count',
        'errors',
        'uploaded_by',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'errors' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    /**
     * Get the admin who uploaded this import
     */
    public function uploadedBy()
    {
        return $this->belongsTo(Admin::class, 'uploaded_by');
    }

    /**
     * Check if import is completed
     */
    public function isCompleted()
    {
        return $this->status === 'completed';
    }

    /**
     * Check if import is failed
     */
    public function isFailed()
    {
        return $this->status === 'failed';
    }

    /**
     * Check if import is processing
     */
    public function isProcessing()
    {
        return $this->status === 'processing';
    }

    /**
     * Get progress percentage
     */
    public function getProgressPercentage()
    {
        if ($this->total_rows == 0) {
            return 0;
        }

        return round(($this->processed_rows / $this->total_rows) * 100, 2);
    }
}
