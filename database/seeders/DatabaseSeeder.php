<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ==========================================
        // 1. USERS (1 Admin, 1 Member)
        // ==========================================

        User::create([
            'name' => 'Admin Hurts',
            'email' => 'admin@hurtsspace.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
        ]);

        User::create([
            'name' => 'Zidan Buyer',
            'email' => 'member@hurtsspace.com',
            'password' => Hash::make('password'),
            'role' => 'member',
            'points' => 100,
            'phone' => '081234567890',
            'address_detail' => 'Jl. Sudirman No. 1, Jakarta Pusat',
        ]);
    }
}
