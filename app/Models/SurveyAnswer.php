<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SurveyAnswer extends Model
{
    protected $fillable = ['survey_response_id', 'survey_question_id', 'answer'];

    public function question(): BelongsTo
    {
        return $this->belongsTo(SurveyQuestion::class, 'survey_question_id');
    }

    public function formatted(): string
    {
        $decoded = json_decode($this->answer ?? '', true);
        if (is_array($decoded)) {
            return implode(', ', $decoded);
        }
        return (string) $this->answer;
    }
}
