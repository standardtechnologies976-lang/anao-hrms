<?php

namespace App\Models;

use Illuminate\Auth\Authenticatable as AuthenticatableTrait;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Citizen extends Model implements Authenticatable
{
    use SoftDeletes, AuthenticatableTrait;

    protected $fillable = [
        'citizen_no', 'last_name', 'first_name', 'middle_name', 'suffix',
        'sex', 'birth_date', 'birth_place', 'civil_status', 'contact_no',
        'barangay_id', 'household_id', 'relationship_to_head', 'purok', 'philhealth_no', 'philsys_no',
        'occupation', 'blood_type', 'religion', 'indigenous_group',
        'is_pwd', 'is_senior', 'is_4ps', 'is_solo_parent', 'is_pregnant',
        'deceased', 'date_of_death', 'remarks',
        'portal_email', 'password', 'portal_enrolled_at',
        'verification_status', 'self_registered', 'verified_at', 'verified_by', 'verification_remarks',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'date_of_death' => 'date',
            'portal_enrolled_at' => 'datetime',
            'verified_at' => 'datetime',
            'self_registered' => 'boolean',
            'password' => 'hashed',
            'is_pwd' => 'boolean',
            'is_senior' => 'boolean',
            'is_4ps' => 'boolean',
            'is_solo_parent' => 'boolean',
            'is_pregnant' => 'boolean',
            'deceased' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::created(function (Citizen $citizen) {
            if (empty($citizen->citizen_no)) {
                $citizen->citizen_no = 'ANAO-'.$citizen->created_at->format('Y').'-'.str_pad((string) $citizen->id, 6, '0', STR_PAD_LEFT);
                $citizen->saveQuietly();
            }
        });
    }

    public function barangay(): BelongsTo
    {
        return $this->belongsTo(Barangay::class);
    }

    public function household(): BelongsTo
    {
        return $this->belongsTo(Household::class);
    }

    public function idDocuments(): HasMany
    {
        return $this->hasMany(CitizenIdDocument::class)->orderByDesc('id');
    }

    public function isVerified(): bool
    {
        return $this->verification_status === 'verified';
    }

    public function isFlagged(): bool
    {
        return $this->verification_status === 'flagged';
    }

    public function isPendingVerification(): bool
    {
        return $this->verification_status === 'pending';
    }

    public function consultations(): HasMany
    {
        return $this->hasMany(Consultation::class)->orderByDesc('consulted_at');
    }

    public function labOrders(): HasMany
    {
        return $this->hasMany(LabOrder::class)->orderByDesc('ordered_at');
    }

    public function radiologyOrders(): HasMany
    {
        return $this->hasMany(RadiologyOrder::class)->orderByDesc('ordered_at');
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class)->orderByDesc('preferred_date');
    }

    public function isPortalEnrolled(): bool
    {
        return ! is_null($this->portal_enrolled_at) && ! is_null($this->password);
    }

    public function getFullNameAttribute(): string
    {
        $name = trim("{$this->last_name}, {$this->first_name} {$this->middle_name}");
        return $this->suffix ? "{$name} {$this->suffix}" : $name;
    }

    public function getAgeAttribute(): ?int
    {
        return $this->birth_date?->age;
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (! $term) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($term) {
            $q->where('last_name', 'like', "%{$term}%")
              ->orWhere('first_name', 'like', "%{$term}%")
              ->orWhere('middle_name', 'like', "%{$term}%")
              ->orWhere('citizen_no', 'like', "%{$term}%")
              ->orWhere('philhealth_no', 'like', "%{$term}%")
              ->orWhere('contact_no', 'like', "%{$term}%");
        });
    }
}
