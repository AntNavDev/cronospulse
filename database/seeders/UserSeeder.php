<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Seed the users table with the default admin user.
     */
    public function run(): void
    {
        User::firstOrCreate(
            ['username' => 'anthony'],
            [
                'name' => 'Anthony',
                'email' => 'anthony@cronospulse.com',
                'password' => 'password',
                'is_admin' => true,
            ],
        );
    }
}
