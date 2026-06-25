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
        // Create an admin user
        User::create([
            'first_name' => 'Miracle',
            'last_name' => 'Obodoeze',
            'user_type' => 'user',
            'email' => 'lorduser@gmail.com',
            'password' => Hash::make('12345678'),
        ]);

        // Create a test user
        User::create([
            'first_name' => 'Miracle',
            'last_name' => 'Obodoeze',
            'user_type' => 'rider',
            'email' => 'lordrider@gmail.com',
            'password' => Hash::make('12345678'),
        ]);
    }
}
