<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Announcement extends Model
{
    use HasFactory, SoftDeletes;

    public const CATEGORIES = ['general', 'policy', 'event', 'urgent'];

    protected $fillable = [
        'title',
        'body',
        'category',
        'is_published',
        'published_at',
        'author_id',
        'pinned',
    ];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'pinned' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true)
            ->where(function (Builder $q) {
                $q->whereNull('published_at')->orWhere('published_at', '<=', now());
            });
    }

    public function scopeVisibleOrder(Builder $query): Builder
    {
        return $query->orderByDesc('pinned')->orderByDesc('published_at')->orderByDesc('created_at');
    }
}
