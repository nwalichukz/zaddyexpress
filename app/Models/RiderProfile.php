<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RiderProfile extends Model
{

protected $fillable = [
    'user_id',
    'legal_name',
    'mobile_number',
    'service_zone',
    'nin',
    'gender',
    'state',
    'mobility_type',
    'plate_number',
    'image',
    'status',
    'total_trips',
    'is_available',
    ];

    /**
     * A rider profile belongs to a user
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

}
