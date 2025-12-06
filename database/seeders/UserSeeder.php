<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create test user
        User::create([
            'name' => 'Test User',
            'email' => 'test@baikfinansial.com',
            'password' => Hash::make('password123'),
        ]);

        // Create demo user
        User::create([
            'name' => 'Demo User',
            'email' => 'demo@baikfinansial.com',
            'password' => Hash::make('demo123'),
        ]);

        // Create Galih user
        User::create([
            'name' => 'Galih',
            'email' => 'galih@baikfinansial.com',
            'password' => Hash::make('galih123'),
        ]);
    }
}
