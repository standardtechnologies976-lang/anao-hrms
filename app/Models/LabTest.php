<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LabTest extends Model
{
    protected $fillable = ['name', 'category', 'unit', 'reference_range', 'price', 'active'];

    protected function casts(): array
    {
        return ['active' => 'boolean'];
    }
}
