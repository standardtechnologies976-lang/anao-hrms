<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class TriageAssessment extends Model
{
    protected $fillable = [
        'citizen_id', 'assessed_at', 'chief_complaint',
        'temperature', 'systolic', 'diastolic', 'pulse', 'respiratory_rate',
        'oxygen_saturation', 'height_cm', 'weight_kg', 'bmi', 'pain_scale',
        'priority', 'status', 'notes', 'assessed_by',
    ];

    protected function casts(): array
    {
        return [
            'assessed_at' => 'datetime',
            'temperature' => 'decimal:1',
            'height_cm' => 'decimal:1',
            'weight_kg' => 'decimal:1',
            'bmi' => 'decimal:1',
        ];
    }

    public function citizen(): BelongsTo
    {
        return $this->belongsTo(Citizen::class);
    }

    public function assessor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assessed_by');
    }

    public function consultation(): HasOne
    {
        return $this->hasOne(Consultation::class, 'triage_assessment_id');
    }

    public function getBloodPressureAttribute(): ?string
    {
        return ($this->systolic && $this->diastolic)
            ? "{$this->systolic}/{$this->diastolic}"
            : null;
    }

    public static function computeBmi(?float $heightCm, ?float $weightKg): ?float
    {
        if (! $heightCm || ! $weightKg || $heightCm <= 0) {
            return null;
        }
        $m = $heightCm / 100;
        return round($weightKg / ($m * $m), 1);
    }
}
