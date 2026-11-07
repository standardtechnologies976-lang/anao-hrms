<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssistanceType extends Model
{
    protected $fillable = ['name', 'category', 'active'];

    protected function casts(): array
    {
        return ['active' => 'boolean'];
    }
}
