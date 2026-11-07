<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NursingNote extends Model
{
    protected $fillable = [
        'admission_id', 'noted_at', 'note', 'temperature', 'systolic',
        'diastolic', 'pulse', 'respiratory_rate', 'oxygen_saturation', 'nurse_id',
    ];

    protected function casts(): array
    {
        return ['noted_at' => 'datetime', 'temperature' => 'decimal:1'];
    }

    public function admission(): BelongsTo
    {
        return $this->belongsTo(Admission::class);
    }

    public function nurse(): BelongsTo
    {
        return $this->belongsTo(User::class, 'nurse_id');
    }

    public function getBloodPressureAttribute(): ?string
    {
        return ($this->systolic && $this->diastolic) ? "{$this->systolic}/{$this->diastolic}" : null;
    }
}
