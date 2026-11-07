<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DispenseItem extends Model
{
    protected $fillable = ['dispense_id', 'medicine_id', 'quantity', 'instructions'];

    public function dispense(): BelongsTo
    {
        return $this->belongsTo(Dispense::class);
    }

    public function medicine(): BelongsTo
    {
        return $this->belongsTo(Medicine::class);
    }
}
