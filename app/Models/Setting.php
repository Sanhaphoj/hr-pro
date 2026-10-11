<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Setting extends Model
{
    protected $fillable = ['key', 'value', 'updated_by'];

    protected function casts(): array
    {
        // JSON keeps the type: a toggle comes back a bool, working days an array.
        return ['value' => 'json'];
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
