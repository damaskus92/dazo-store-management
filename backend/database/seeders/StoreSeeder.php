<?php

namespace Database\Seeders;

use App\Models\Store;
use Illuminate\Database\Seeder;

class StoreSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Toko Pusat
        $center = Store::factory()
            ->center()
            ->create([
                'name' => 'Toko Pusat Utama',
            ]);

        // Toko Cabang
        $branches = Store::factory()
            ->count(2)
            ->branch($center)
            ->create();

        // Toko Retail
        foreach ($branches as $branch) {
            Store::factory()
                ->count(2)
                ->retail($branch)
                ->create();
        }
    }
}
