<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Job extends Model
{
     use HasFactory;
 
    protected $fillable = [
        'user_id',
        'title',
        'description',
        'pickup_address',
        'pickup_lat',
        'pickup_lng',
        'dropoff_lat',
        'dropoff_lng',
        'dropoff_address',
        'mobility_type_needed',
        'price',
        'price_type',
        'status',
        'posted_at',
        'expires_at',
        'delivered_at',
    ];
 
    protected $casts = [
        'price'        => 'decimal:2',
        'pickup_lat'   => 'float',
        'pickup_lng'   => 'float',
        'posted_at'    => 'datetime',
        'expires_at'   => 'datetime',
        'delivered_at' => 'datetime',
    ];
 
    // ── Relationships ─────────────────────────────────────────────────────────
 
   public function user(): BelongsTo
     {
    return $this->belongsTo(User::class);
         }
 
    public function applications(): HasMany
    {
        return $this->hasMany(JobApplication::class);
    }


    public function jobItem(): HasMany
    {
        return $this->hasMany(JobItem::class);
    }
 
    public function acceptedApplication(): HasOne
    {
        return $this->hasOne(JobApplication::class)
                    ->where('status', 'accepted');
    }
 
    // ── Scopes ────────────────────────────────────────────────────────────────
 
    public function scopeOpen($query)
    {
        return $query->where('status', 'open');
    }
 
    public function scopeActive($query)
    {
        return $query->whereIn('status', ['open', 'matched', 'in_progress']);
    }
 
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }
 
    public function scopeNotExpired($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')
              ->orWhere('expires_at', '>', now());
        });
    }
 
    public function scopeByMobilityType($query, string $type)
    {
        return $query->where('mobility_type_needed', $type);
    }
 
    // ── Accessors ─────────────────────────────────────────────────────────────
 
    /**
     * Returns true if the job is past its expiry date.
     */
    public function getIsExpiredAttribute(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }
 
    /**
     * Returns true if the job is accepting applications.
     */
    public function getIsAcceptingApplicationsAttribute(): bool
    {
        return $this->status === 'open' && ! $this->is_expired;
    }

}
