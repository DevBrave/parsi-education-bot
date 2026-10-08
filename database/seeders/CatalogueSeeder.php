<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\Professor;
use App\Models\Program;
use Illuminate\Database\Seeder;

class CatalogueSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Programme
        $program = Program::firstOrCreate(
            ['name' => 'Computer Engineering', 'degree_level' => 'MSc'],
            ['university' => 'Politecnico di Torino'],
        );

        // 2. Course
        $course = Course::firstOrCreate(
            ['course_code' => '01TXFSM'],
            [
                'name' => 'Internet of Things',
                'credits' => 6.0,
            ],
        );

        // 3. Link course to programme (pivot)
        $program->courses()->syncWithoutDetaching([$course->id]);

        // 4. Professor
        $professor = Professor::firstOrCreate(
            ['full_name' => 'Fulvio Corno'],
            ['university_email' => 'fulvio.corno@polito.it'],
        );

        // 5. Another Programme & Course for testing multi-button navigation
        $elecProgram = Program::firstOrCreate(
            ['name' => 'Electronic Engineering', 'degree_level' => 'MSc'],
            ['university' => 'Politecnico di Torino'],
        );

        $circuitCourse = Course::firstOrCreate(
            ['course_code' => '02ELCSM'],
            [
                'name' => 'Circuit Analysis',
                'credits' => 6.0,
            ],
        );

        $elecProgram->courses()->syncWithoutDetaching([$circuitCourse->id]);
    }
}
