<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssistanceRequest extends Model
{
    protected $fillable = [
        'reference_no', 'citizen_id', 'assistance_type_id', 'request_date', 'purpose',
        'amount_requested', 'amount_granted', 'status', 'remarks', 'processed_by', 'released_at',
    ];

    protected function casts(): array
    {
        return [
            'request_date' => 'date',
            'released_at' => 'datetime',
            'amount_requested' => 'decimal:2',
            'amount_granted' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::created(function (AssistanceRequest $r) {
            if (empty($r->reference_no)) {
                $r->reference_no = 'MSWD-'.$r->created_at->format('Y').'-'.str_pad((string) $r->id, 5, '0', STR_PAD_LEFT);
                $r->saveQuietly();
            }
        });
    }

    public function citizen(): BelongsTo
    {
        return $this->belongsTo(Citizen::class);
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(AssistanceType::class, 'assistance_type_id');
    }

    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }
}
