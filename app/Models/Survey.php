<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Survey extends Model
{
    protected $fillable = ['title', 'slug', 'description', 'status', 'is_public', 'created_by'];

    protected function casts(): array
    {
        return ['is_public' => 'boolean'];
    }

    protected static function booted(): void
    {
        static::creating(function (Survey $s) {
            if (empty($s->slug)) {
                $base = Str::slug($s->title) ?: 'survey';
                $s->slug = $base.'-'.Str::lower(Str::random(6));
            }
        });
    }

    public function questions(): HasMany
    {
        return $this->hasMany(SurveyQuestion::class)->orderBy('sort_order')->orderBy('id');
    }

    public function responses(): HasMany
    {
        return $this->hasMany(SurveyResponse::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isOpen(): bool
    {
        return $this->status === 'published';
    }

    public function publicUrl(): string
    {
        return route('survey.public', $this->slug);
    }
}
