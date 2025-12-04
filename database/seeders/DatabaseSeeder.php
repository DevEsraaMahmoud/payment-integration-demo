<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        // Create admin user
        User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@admin.com',
            'password' => Hash::make('password'),
            'is_admin' => true,
        ]);

        // Create regular user
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@test.com',
            'password' => Hash::make('password'),
            'is_admin' => false,
        ]);

        $this->call([
            CategorySeeder::class,
            ProductSeeder::class,
        ]);
    }
}
