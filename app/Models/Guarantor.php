<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo; // ← correct

use Illuminate\Database\Eloquent\Model;

class Guarantor extends Model
{    
     use HasFactory; 
     protected $fillable = [
        'rider_profile_id',
        'name',
        'state',
        'address',
        'mobile_no',
        'nin',
        'image',
    ];

    /**
     * NIN is sensitive — exclude from default serialisation.
     */
    protected $hidden = ['nin'];

    // ── Relationships ─────────────────────────────────────────────────────────

    public function riderProfile(): BelongsTo
    {
        return $this->belongsTo(RiderProfile::class);
    }

    // ── Accessors ─────────────────────────────────────────────────────────────

    public function getImageUrlAttribute(): ?string
    {
        return $this->image ? asset('storage/' . $this->image) : null;
    }

    // ── Scopes ────────────────────────────────────────────────────────────────

    public function scopeInState($query, string $state)
    {
        return $query->where('state', $state);
    }
}
