<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RadiologyOrder extends Model
{
    protected $fillable = [
        'citizen_id', 'consultation_id', 'imaging_exam_id', 'ordered_by', 'ordered_at',
        'clinical_indication', 'status', 'performed_at', 'findings', 'impression',
        'reported_by', 'released_at',
    ];

    protected function casts(): array
    {
        return [
            'ordered_at' => 'datetime',
            'performed_at' => 'datetime',
            'released_at' => 'datetime',
        ];
    }

    public function citizen(): BelongsTo
    {
        return $this->belongsTo(Citizen::class);
    }

    public function exam(): BelongsTo
    {
        return $this->belongsTo(ImagingExam::class, 'imaging_exam_id');
    }

    public function orderer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ordered_by');
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by');
    }
}
