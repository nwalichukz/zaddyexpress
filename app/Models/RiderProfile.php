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
    'current_latitude',
    'current_longitude',
    'review_rank',
    'mobility_type',
    'mobility_brand',
    'mobility_model',
    'production_year',
    'current_lat',
    'current_lng',
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


      /**
     * A rider profile belongs to a user
     */
    public function guarantors()
    {
        return $this->hasMany(Guarantor::class);
    }

}
