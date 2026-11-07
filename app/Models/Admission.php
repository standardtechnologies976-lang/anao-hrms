<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Admission extends Model
{
    protected $fillable = [
        'citizen_id', 'consultation_id', 'ward_id', 'bed_id', 'admitted_at',
        'admitting_diagnosis', 'attending_physician', 'status', 'discharged_at',
        'discharge_diagnosis', 'outcome', 'discharge_notes', 'admitted_by',
    ];

    protected function casts(): array
    {
        return [
            'admitted_at' => 'datetime',
            'discharged_at' => 'datetime',
        ];
    }

    public function citizen(): BelongsTo
    {
        return $this->belongsTo(Citizen::class);
    }

    public function ward(): BelongsTo
    {
        return $this->belongsTo(Ward::class);
    }

    public function bed(): BelongsTo
    {
        return $this->belongsTo(Bed::class);
    }

    public function nursingNotes(): HasMany
    {
        return $this->hasMany(NursingNote::class)->orderByDesc('noted_at');
    }

    public function admitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admitted_by');
    }
}
