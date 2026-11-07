<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Dispense extends Model
{
    protected $fillable = [
        'citizen_id', 'consultation_id', 'dispensed_at', 'dispensed_by', 'notes',
    ];

    protected function casts(): array
    {
        return ['dispensed_at' => 'datetime'];
    }

    public function citizen(): BelongsTo
    {
        return $this->belongsTo(Citizen::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(DispenseItem::class);
    }

    public function dispenser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dispensed_by');
    }
}
