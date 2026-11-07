<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImagingExam extends Model
{
    protected $fillable = ['name', 'modality', 'price', 'active'];

    protected function casts(): array
    {
        return ['active' => 'boolean'];
    }
}
