<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FieldVisit extends Model
{
    protected $fillable = [
        'bhw_id', 'barangay_id', 'citizen_id', 'household_id', 'visit_date',
        'visit_type', 'findings', 'action_taken', 'referred', 'referral_notes',
    ];

    protected function casts(): array
    {
        return ['visit_date' => 'date', 'referred' => 'boolean'];
    }

    public function bhw(): BelongsTo
    {
        return $this->belongsTo(User::class, 'bhw_id');
    }

    public function citizen(): BelongsTo
    {
        return $this->belongsTo(Citizen::class);
    }

    public function household(): BelongsTo
    {
        return $this->belongsTo(Household::class);
    }

    public function barangay(): BelongsTo
    {
        return $this->belongsTo(Barangay::class);
    }
}
