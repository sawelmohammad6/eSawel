<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $email = (string) (env('ADMIN_EMAIL') ?: 'admin@example.com');

        User::query()->firstOrCreate(
            ['email' => $email],
            [
                'name' => (string) (env('ADMIN_NAME') ?: 'Administrator'),
                'phone' => env('ADMIN_PHONE'),
                'password' => (string) (env('ADMIN_PASSWORD') ?: 'password'),
                'role' => 'admin',
                'status' => 'active',
            ]
        );
    }
}

