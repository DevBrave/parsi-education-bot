<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use Illuminate\Database\Seeder;

class AcademicYearSeeder extends Seeder
{
    public function run(): void
    {
        $years = [
            ['label' => '2021/22', 'starts_on' => '2021-10-01', 'ends_on' => '2022-09-30', 'is_current' => false],
            ['label' => '2022/23', 'starts_on' => '2022-10-01', 'ends_on' => '2023-09-30', 'is_current' => false],
            ['label' => '2023/24', 'starts_on' => '2023-10-01', 'ends_on' => '2024-09-30', 'is_current' => false],
            ['label' => '2024/25', 'starts_on' => '2024-10-01', 'ends_on' => '2025-09-30', 'is_current' => false],
            ['label' => '2025/26', 'starts_on' => '2025-10-01', 'ends_on' => '2026-09-30', 'is_current' => false],
            ['label' => '2026/27', 'starts_on' => '2026-10-01', 'ends_on' => '2027-09-30', 'is_current' => true],
        ];

        foreach ($years as $data) {
            AcademicYear::updateOrCreate(
                ['label' => $data['label']],
                [
                    'starts_on' => $data['starts_on'],
                    'ends_on' => $data['ends_on'],
                    'is_current' => $data['is_current'],
                ]
            );
        }
    }
}
