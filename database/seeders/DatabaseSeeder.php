<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            ResourceTypeSeeder::class,   // 8 resource types
            AcademicYearSeeder::class,   // 2025/26
            CatalogueSeeder::class,      // 1 programme, 1 course, 1 professor
            TestResourceSeeder::class,   // 1 approved resource + file
        ]);
    }
}
