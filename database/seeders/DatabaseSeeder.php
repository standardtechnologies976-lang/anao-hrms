<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Reference data
        $this->call([
            BarangaySeeder::class,
        ]);

        // Default administrator (change the password after first login)
        User::firstOrCreate(
            ['email' => 'admin@anao.gov.ph'],
            [
                'name' => 'System Administrator',
                'password' => Hash::make('password'),
                'role' => 'super_admin',
                'position' => 'MHO Administrator',
                'is_admin' => true,
                'permissions' => [],
            ]
        );
    }
}
