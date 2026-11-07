<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Appointment extends Model
{
    protected $fillable = [
        'reference_no', 'citizen_id', 'service', 'preferred_date', 'preferred_time',
        'reason', 'status', 'scheduled_at', 'remarks', 'source',
    ];

    protected function casts(): array
    {
        return ['preferred_date' => 'date', 'scheduled_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::created(function (Appointment $a) {
            if (empty($a->reference_no)) {
                $a->reference_no = 'APPT-'.$a->created_at->format('Y').'-'.str_pad((string) $a->id, 5, '0', STR_PAD_LEFT);
                $a->saveQuietly();
            }
        });
    }

    public function citizen(): BelongsTo
    {
        return $this->belongsTo(Citizen::class);
    }
}
