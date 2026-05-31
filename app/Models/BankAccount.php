<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BankAccount extends Model
{
     protected $fillable = [
        'user_id',
        'account_number',
        'bank_name',
        'status',
    ];
}
