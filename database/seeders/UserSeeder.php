<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create user
        User::create([
            'first_name' => 'Miracle',
            'last_name' => 'Obodoeze',
            'user_type' => 'user',
            'status' => 'active',
            'is_verified' => 'yes',
            'mobile_number'=> '08065119492',
            'email' => 'lorduser@gmail.com',
            'password' => Hash::make('12345678'),
        ]);
        
        // Create a rider
        User::create([
            'first_name' => 'Miracle',
            'last_name' => 'Obodoeze',
            'status' => 'active',
            'is_verified' => 'yes',
            'user_type' => 'rider',
            'mobile_number'=> '07065119492',
            'email' => 'lordrider@gmail.com',
            'password' => Hash::make('12345678'),
        ]);

    }
}
