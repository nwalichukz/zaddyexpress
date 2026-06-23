<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserProfile extends Model
{
      use HasFactory;

    protected $fillable = [
        'user_id',
        'last_name',
        'last_name',
        'other_name',
        'mobile_number',
        'gender',
    ];

    // ── Relationships ─────────────────────────────────────────────────────────git

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ── Accessors ─────────────────────────────────────────────────────────────

    /**
     * Returns the user's full name as a single string.
     * e.g. "Obi Chukwuma Emmanuel"
     */
    public function getFullNameAttribute(): string
    {
        return trim("{$this->sur_name} {$this->last_name} {$this->other_name}");
    }

    
}
