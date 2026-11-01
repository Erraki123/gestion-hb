<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name'              => 'Administrateur',
                'password'          => Hash::make('ChangeMoi123!'),
                'role'              => 'admin',
                'is_active'         => true,
                'email_verified_at' => now(),
            ]
        );
    }
}
