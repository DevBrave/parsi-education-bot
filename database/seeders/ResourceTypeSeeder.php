<?php

namespace Database\Seeders;

use App\Models\ResourceType;
use Illuminate\Database\Seeder;

class ResourceTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['code' => 'past_exam',          'display_name' => 'Past Exams',          'sort_order' => 1],
            ['code' => 'exam_solution',      'display_name' => 'Exam Solutions',      'sort_order' => 2],
            ['code' => 'notes',              'display_name' => 'Notes & Summaries',   'sort_order' => 3],
            ['code' => 'exercises',          'display_name' => 'Exercises',           'sort_order' => 4],
            ['code' => 'exercise_solution',  'display_name' => 'Exercise Solutions',  'sort_order' => 5],
            ['code' => 'slides',             'display_name' => 'Lecture Slides',      'sort_order' => 6],
            ['code' => 'video',              'display_name' => 'Educational Videos',  'sort_order' => 7],
            ['code' => 'link',               'display_name' => 'Useful Links',        'sort_order' => 8],
        ];

        foreach ($types as $type) {
            ResourceType::firstOrCreate(['code' => $type['code']], $type);
        }
    }
}
