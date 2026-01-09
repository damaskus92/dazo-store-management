<?php

namespace Database\Seeders;

use App\Models\Store;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // SUPER ADMIN
        User::factory()
            ->unverified()
            ->superAdmin()
            ->create([
                'first_name' => 'Super',
                'last_name' => 'Admin',
                'email' => 'superadmin@example.com',
            ]);

        // ADMIN & KASIR per STORE
        $stores = Store::all();

        foreach ($stores as $store) {
            User::factory()
                ->unverified()
                ->admin($store)
                ->create([
                    'email' => 'admin_' . Str::lower(Str::random(5)) . '@example.com',
                ]);

            User::factory()
                ->unverified()
                ->cashier($store)
                ->create([
                    'email' => 'cashier_' . Str::lower(Str::random(5)) . '@example.com',
                ]);
        }
    }
}
