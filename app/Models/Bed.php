<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Bed extends Model
{
    protected $fillable = ['ward_id', 'label', 'is_occupied'];

    protected function casts(): array
    {
        return ['is_occupied' => 'boolean'];
    }

    public function ward(): BelongsTo
    {
        return $this->belongsTo(Ward::class);
    }
}
