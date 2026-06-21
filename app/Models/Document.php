<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Document extends Model
{
    public const CATEGORY_LABELS = [
        'general' => 'ทั่วไป',
        'policy' => 'นโยบาย',
        'form' => 'แบบฟอร์ม',
        'contract' => 'สัญญา',
    ];

    protected $fillable = [
        'title', 'category', 'file_path', 'original_name',
        'mime', 'size', 'uploaded_by', 'is_public',
    ];

    protected function casts(): array
    {
        return [
            'is_public' => 'boolean',
            'size' => 'integer',
        ];
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function getSizeLabelAttribute(): string
    {
        $bytes = (int) $this->size;
        if ($bytes <= 0) {
            return '—';
        }
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = (int) floor(log($bytes, 1024));
        $i = min($i, count($units) - 1);

        return round($bytes / (1024 ** $i), 1).' '.$units[$i];
    }
}
