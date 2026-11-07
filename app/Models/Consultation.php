<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Consultation extends Model
{
    protected $fillable = [
        'citizen_id', 'triage_assessment_id', 'physician_id', 'consulted_at',
        'subjective', 'objective', 'assessment', 'plan',
        'diagnosis', 'icd10_code',
        'disposition', 'follow_up_date', 'referred_to', 'referral_notes', 'status',
    ];

    protected function casts(): array
    {
        return [
            'consulted_at' => 'datetime',
            'follow_up_date' => 'date',
        ];
    }

    public function citizen(): BelongsTo
    {
        return $this->belongsTo(Citizen::class);
    }

    public function triage(): BelongsTo
    {
        return $this->belongsTo(TriageAssessment::class, 'triage_assessment_id');
    }

    public function physician(): BelongsTo
    {
        return $this->belongsTo(User::class, 'physician_id');
    }

    public function prescriptions(): HasMany
    {
        return $this->hasMany(PrescriptionItem::class);
    }
}
