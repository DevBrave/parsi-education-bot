<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\Course;
use App\Models\Professor;
use App\Models\Resource;
use App\Models\ResourceFile;
use App\Models\ResourceType;
use App\Models\User;
use Illuminate\Database\Seeder;

class TestResourceSeeder extends Seeder
{
    public function run(): void
    {
        // Create a test admin user (simulates a Telegram user)
        $admin = User::firstOrCreate(
            ['telegram_user_id' => 123456789],
            [
                'username'           => 'test_admin',
                'first_name'         => 'Test',
                'last_name'          => 'Admin',
                'preferred_language' => 'en',
                'role'               => 'admin',
            ],
        );

        // Get seeded data
        $course       = Course::where('course_code', '01TXFSM')->first();
        $examType     = ResourceType::where('code', 'past_exam')->first();
        $academicYear = AcademicYear::where('label', '2025/26')->first();
        $professor    = Professor::where('full_name', 'Fulvio Corno')->first();

        if (!$course || !$examType || !$academicYear || !$professor) {
            $this->command->warn('Run ResourceTypeSeeder, AcademicYearSeeder, and CatalogueSeeder first.');
            return;
        }

        // Create one approved resource
        $resource = Resource::firstOrCreate(
            ['title' => 'IoT Final Exam — January 2026'],
            [
                'course_id'        => $course->id,
                'resource_type_id' => $examType->id,
                'academic_year_id' => $academicYear->id,
                'exam_date'        => '2026-01-20',
                'language'         => 'en',
                'has_solutions'    => false,
                'status'           => 'approved',
                'submitted_by'     => $admin->id,
                'reviewed_by'      => $admin->id,
                'reviewed_at'      => now(),
            ],
        );

        // Link professor to resource (pivot)
        $resource->professors()->syncWithoutDetaching([$professor->id]);

        // Create a placeholder resource file
        // (telegram_file_id will be filled when you actually upload a file)
        ResourceFile::firstOrCreate(
            ['resource_id' => $resource->id, 'sha256' => str_repeat('a', 64)],
            [
                'original_filename'       => 'IoT_Exam_Jan2026.pdf',
                'mime_type'               => 'application/pdf',
                'size_bytes'              => 102400,
                'drive_file_id'           => null,
                'telegram_file_id'        => null,
                'telegram_file_unique_id' => null,
            ],
        );

        $this->command->info("Test resource created: {$resource->title}");
    }
}
