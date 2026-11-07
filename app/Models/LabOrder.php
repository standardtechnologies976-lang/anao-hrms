<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LabOrder extends Model
{
    protected $fillable = [
        'citizen_id', 'consultation_id', 'ordered_by', 'ordered_at', 'status', 'remarks', 'released_at',
    ];

    protected function casts(): array
    {
        return ['ordered_at' => 'datetime', 'released_at' => 'datetime'];
    }

    public function citizen(): BelongsTo
    {
        return $this->belongsTo(Citizen::class);
    }

    public function orderer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ordered_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(LabOrderItem::class);
    }
}
