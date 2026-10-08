<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use Illuminate\Database\Seeder;

class AcademicYearSeeder extends Seeder
{
    public function run(): void
    {
        AcademicYear::firstOrCreate(
            ['label' => '2025/26'],
            [
                'starts_on' => '2025-10-01',
                'ends_on' => '2026-09-30',
                'is_current' => true,
            ],
        );
    }
}
