<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    protected $fillable = [
        'medicine_id', 'type', 'quantity', 'batch_no', 'expiry_date',
        'unit_cost', 'reference', 'dispense_id', 'moved_at', 'user_id',
    ];

    protected function casts(): array
    {
        return ['expiry_date' => 'date', 'moved_at' => 'datetime'];
    }

    public function medicine(): BelongsTo
    {
        return $this->belongsTo(Medicine::class);
    }
}
