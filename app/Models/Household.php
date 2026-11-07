<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Household extends Model
{
    protected $fillable = [
        'household_no', 'barangay_id', 'purok', 'address', 'head_citizen_id',
        'house_ownership', 'house_type', 'water_source', 'toilet_facility',
        'has_electricity', 'waste_disposal', 'income_bracket',
        'surveyed_at', 'surveyed_by', 'remarks',
    ];

    protected function casts(): array
    {
        return ['has_electricity' => 'boolean', 'surveyed_at' => 'date'];
    }

    protected static function booted(): void
    {
        static::created(function (Household $h) {
            if (empty($h->household_no)) {
                $h->household_no = 'HH-'.$h->created_at->format('Y').'-'.str_pad((string) $h->id, 5, '0', STR_PAD_LEFT);
                $h->saveQuietly();
            }
        });
    }

    public function barangay(): BelongsTo
    {
        return $this->belongsTo(Barangay::class);
    }

    public function head(): BelongsTo
    {
        return $this->belongsTo(Citizen::class, 'head_citizen_id');
    }

    public function members(): HasMany
    {
        return $this->hasMany(Citizen::class, 'household_id')->orderBy('last_name');
    }
}
