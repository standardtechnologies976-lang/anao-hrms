<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SurveyQuestion extends Model
{
    protected $fillable = ['survey_id', 'sort_order', 'label', 'type', 'options', 'required'];

    protected function casts(): array
    {
        return ['options' => 'array', 'required' => 'boolean'];
    }

    public function survey(): BelongsTo
    {
        return $this->belongsTo(Survey::class);
    }

    public function isChoice(): bool
    {
        return in_array($this->type, ['radio', 'checkbox'], true);
    }
}
