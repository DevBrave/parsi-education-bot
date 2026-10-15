<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\Course;
use App\Models\Program;
use App\Models\Resource;
use App\Models\ResourceFile;
use App\Models\ResourceType;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CompleteCatalogSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Admin & Submitter User
        $user = User::firstOrCreate(
            ['telegram_user_id' => 508762226],
            [
                'username' => 'devbrave',
                'first_name' => 'Hamidreza',
                'last_name' => 'Ramezani',
                'preferred_language' => 'fa',
                'role' => 'admin',
            ]
        );

        // 2. Academic Years (2021 to 2027)
        $academicYears = [
            ['label' => '2021/22', 'starts_on' => '2021-10-01', 'ends_on' => '2022-09-30', 'is_current' => false],
            ['label' => '2022/23', 'starts_on' => '2022-10-01', 'ends_on' => '2023-09-30', 'is_current' => false],
            ['label' => '2023/24', 'starts_on' => '2023-10-01', 'ends_on' => '2024-09-30', 'is_current' => false],
            ['label' => '2024/25', 'starts_on' => '2024-10-01', 'ends_on' => '2025-09-30', 'is_current' => false],
            ['label' => '2025/26', 'starts_on' => '2025-10-01', 'ends_on' => '2026-09-30', 'is_current' => false],
            ['label' => '2026/27', 'starts_on' => '2026-10-01', 'ends_on' => '2027-09-30', 'is_current' => true],
        ];

        foreach ($academicYears as $yearData) {
            AcademicYear::updateOrCreate(['label' => $yearData['label']], $yearData);
        }

        // 3. Resource Types
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
            ResourceType::updateOrCreate(['code' => $type['code']], $type);
        }

        // 4. Programmes (5 Degree Programmes)
        $programsData = [
            'Computer Engineering' => ['degree_level' => 'BSc', 'university' => 'Politecnico di Torino', 'is_active' => true],
            'Electronic and Communications Engineering' => ['degree_level' => 'BSc', 'university' => 'Politecnico di Torino', 'is_active' => true],
            'Mechanical Engineering' => ['degree_level' => 'BSc', 'university' => 'Politecnico di Torino', 'is_active' => true],
            'Automotive Engineering' => ['degree_level' => 'BSc', 'university' => 'Politecnico di Torino', 'is_active' => true],
            'Civil and Environmental Engineering' => ['degree_level' => 'BSc', 'university' => 'Politecnico di Torino', 'is_active' => true],
        ];

        $programs = [];
        foreach ($programsData as $pName => $pData) {
            $programs[$pName] = Program::updateOrCreate(
                ['name' => $pName],
                $pData
            );
        }

        // Set User's default program to Computer Engineering
        $user->update(['program_id' => $programs['Computer Engineering']->id]);

        // 5. All 67 Courses
        $coursesData = [
            // --- YEAR 1 (9 Courses) ---
            [
                'code' => '06KWR',
                'name' => 'Chemistry',
                'year' => 1,
                'credits' => 8.0,
                'programs' => [
                    'Computer Engineering',
                    'Electronic and Communications Engineering',
                    'Civil and Environmental Engineering',
                    'Automotive Engineering',
                    'Mechanical Engineering',
                ],
            ],
            [
                'code' => '07JCJ',
                'name' => 'Computer Sciences',
                'year' => 1,
                'credits' => 8.0,
                'programs' => [
                    'Computer Engineering',
                    'Electronic and Communications Engineering',
                    'Civil and Environmental Engineering',
                    'Automotive Engineering',
                    'Mechanical Engineering',
                ],
            ],
            [
                'code' => '04KWQ',
                'name' => 'Mathematical Analysis I',
                'year' => 1,
                'credits' => 10.0,
                'programs' => [
                    'Computer Engineering',
                    'Electronic and Communications Engineering',
                    'Civil and Environmental Engineering',
                    'Automotive Engineering',
                    'Mechanical Engineering',
                ],
            ],
            [
                'code' => '03KXT',
                'name' => 'Linear Algebra and Geometry',
                'year' => 1,
                'credits' => 10.0,
                'programs' => [
                    'Computer Engineering',
                    'Electronic and Communications Engineering',
                    'Civil and Environmental Engineering',
                    'Automotive Engineering',
                    'Mechanical Engineering',
                ],
            ],
            [
                'code' => '04KXV',
                'name' => 'Physics I',
                'year' => 1,
                'credits' => 10.0,
                'programs' => [
                    'Computer Engineering',
                    'Electronic and Communications Engineering',
                    'Civil and Environmental Engineering',
                    'Automotive Engineering',
                    'Mechanical Engineering',
                ],
            ],
            [
                'code' => '02OJPYE',
                'name' => 'Programming Techniques',
                'year' => 1,
                'credits' => 6.0,
                'programs' => [
                    'Computer Engineering',
                ],
            ],
            [
                'code' => '04KXXYI-07KXXXP',
                'name' => 'Engineering Drawing',
                'year' => 1,
                'credits' => 10.0,
                'programs' => [
                    'Automotive Engineering',
                    'Mechanical Engineering',
                ],
            ],
            [
                'code' => '01PZGWN',
                'name' => 'Civil & Environmental – Geology',
                'year' => 1,
                'credits' => 6.0,
                'programs' => [
                    'Civil and Environmental Engineering',
                ],
            ],
            [
                'code' => '04LTMWW',
                'name' => 'Communication Networks',
                'year' => 1,
                'credits' => 6.0,
                'programs' => [
                    'Electronic and Communications Engineering',
                ],
            ],

            // --- YEAR 2 (27 Courses) ---
            [
                'code' => '01URNYE',
                'name' => 'Algorithms and Data Structures',
                'year' => 2,
                'credits' => 8.0,
                'programs' => [
                    'Computer Engineering',
                ],
            ],
            [
                'code' => '03LMA',
                'name' => 'Applied Mechanics',
                'year' => 2,
                'credits' => 10.0,
                'programs' => [
                    'Automotive Engineering',
                    'Mechanical Engineering',
                ],
            ],
            [
                'code' => '05LSLWW',
                'name' => 'Automatic Control',
                'year' => 2,
                'credits' => 6.0,
                'programs' => [
                    'Electronic and Communications Engineering',
                ],
            ],
            [
                'code' => '01QVQWW',
                'name' => 'Circuit Theory and Applications',
                'year' => 2,
                'credits' => 10.0,
                'programs' => [
                    'Electronic and Communications Engineering',
                ],
            ],
            [
                'code' => '02KTMYE',
                'name' => 'Computer Architecture',
                'year' => 2,
                'credits' => 8.0,
                'programs' => [
                    'Computer Engineering',
                ],
            ],
            [
                'code' => '01GYBYE',
                'name' => 'Digital Electronics Design',
                'year' => 2,
                'credits' => 8.0,
                'programs' => [
                    'Computer Engineering',
                ],
            ],
            [
                'code' => '01URLYE',
                'name' => 'Electromagnetism and Circuit Theory',
                'year' => 2,
                'credits' => 12.0,
                'programs' => [
                    'Computer Engineering',
                ],
            ],
            [
                'code' => '04OIGWW',
                'name' => 'Electronic Circuits',
                'year' => 2,
                'credits' => 8.0,
                'programs' => [
                    'Electronic and Communications Engineering',
                ],
            ],
            [
                'code' => '02LTHWW',
                'name' => 'Electronic Devices',
                'year' => 2,
                'credits' => 6.0,
                'programs' => [
                    'Electronic and Communications Engineering',
                ],
            ],
            [
                'code' => '01DRSWN',
                'name' => 'Environmental Engineering',
                'year' => 2,
                'credits' => 10.0,
                'programs' => [
                    'Civil and Environmental Engineering',
                ],
            ],
            [
                'code' => '05LNMWN',
                'name' => 'Fluid Mechanics',
                'year' => 2,
                'credits' => 10.0,
                'programs' => [
                    'Civil and Environmental Engineering',
                ],
            ],
            [
                'code' => '04OFTXP',
                'name' => 'Fundamentals of Electrical and Electronic Systems',
                'year' => 2,
                'credits' => 8.0,
                'programs' => [
                    'Automotive Engineering',
                ],
            ],
            [
                'code' => '01NLGYI',
                'name' => 'Fundamentals of Engineering Thermodynamics and Heat Transfer',
                'year' => 2,
                'credits' => 8.0,
                'programs' => [
                    'Automotive Engineering',
                    'Mechanical Engineering',
                ],
            ],
            [
                'code' => '01NLAYI',
                'name' => 'Fundamentals of Strength of Materials',
                'year' => 2,
                'credits' => 8.0,
                'programs' => [
                    'Automotive Engineering',
                    'Mechanical Engineering',
                ],
            ],
            [
                'code' => '01RKWYE',
                'name' => 'Introduction to Databases',
                'year' => 2,
                'credits' => 8.0,
                'programs' => [
                    'Computer Engineering',
                ],
            ],
            [
                'code' => '02KXU',
                'name' => 'Mathematical Analysis II',
                'year' => 2,
                'credits' => 6.0,
                'programs' => [
                    'Civil and Environmental Engineering',
                    'Automotive Engineering',
                    'Mechanical Engineering',
                ],
            ],
            [
                'code' => '07KXUYE',
                'name' => 'Mathematical Analysis II',
                'year' => 2,
                'credits' => 10.0,
                'programs' => [
                    'Computer Engineering',
                ],
            ],
            [
                'code' => '03KXUWW',
                'name' => 'Mathematical Analysis II',
                'year' => 2,
                'credits' => 8.0,
                'programs' => [
                    'Electronic and Communications Engineering',
                ],
            ],
            [
                'code' => '02LSIWW',
                'name' => 'Mathematical Methods',
                'year' => 2,
                'credits' => 10.0,
                'programs' => [
                    'Electronic and Communications Engineering',
                ],
            ],
            [
                'code' => '01UROYE',
                'name' => 'Mathematical Methods for Computer Science',
                'year' => 2,
                'credits' => 10.0,
                'programs' => [
                    'Computer Engineering',
                ],
            ],
            [
                'code' => '01VUGYI',
                'name' => 'Methodologies for Inspection and Quality Control',
                'year' => 2,
                'credits' => 6.0,
                'programs' => [
                    'Mechanical Engineering',
                ],
            ],
            [
                'code' => '05KXW',
                'name' => 'Physics II',
                'year' => 2,
                'credits' => 6.0,
                'programs' => [
                    'Electronic and Communications Engineering',
                    'Civil and Environmental Engineering',
                    'Automotive Engineering',
                    'Mechanical Engineering',
                ],
            ],
            [
                'code' => '15KXWWW',
                'name' => 'Physics II',
                'year' => 2,
                'credits' => 8.0,
                'programs' => [
                    'Electronic and Communications Engineering',
                ],
            ],
            [
                'code' => '02NLCWN',
                'name' => 'Science and Technology of Materials',
                'year' => 2,
                'credits' => 6.0,
                'programs' => [
                    'Civil and Environmental Engineering',
                ],
            ],
            [
                'code' => '01NLMYI',
                'name' => 'Science and Technology of Materials / Metallic Materials',
                'year' => 2,
                'credits' => 10.0,
                'programs' => [
                    'Automotive Engineering',
                    'Mechanical Engineering',
                ],
            ],
            [
                'code' => '07LLQWN',
                'name' => 'Structural Mechanics',
                'year' => 2,
                'credits' => 10.0,
                'programs' => [
                    'Civil and Environmental Engineering',
                ],
            ],
            [
                'code' => '01DRRWN',
                'name' => 'Theoretical Mechanics',
                'year' => 2,
                'credits' => 4.0,
                'programs' => [
                    'Civil and Environmental Engineering',
                ],
            ],

            // --- YEAR 3 (31 Courses) ---
            [
                'code' => '02MZGWW',
                'name' => 'Applied Electronics',
                'year' => 3,
                'credits' => 10.0,
                'programs' => [
                    'Electronic and Communications Engineering',
                ],
            ],
            [
                'code' => '01TUMWW',
                'name' => 'Applied Signal Processing Laboratory',
                'year' => 3,
                'credits' => 8.0,
                'programs' => [
                    'Electronic and Communications Engineering',
                ],
            ],
            [
                'code' => '06LSLYE',
                'name' => 'Automatic Control',
                'year' => 3,
                'credits' => 10.0,
                'programs' => [
                    'Computer Engineering',
                ],
            ],
            [
                'code' => '04LSLXP',
                'name' => 'Automatic Control',
                'year' => 3,
                'credits' => 8.0,
                'programs' => [
                    'Automotive Engineering',
                ],
            ],
            [
                'code' => '01DRVWN',
                'name' => 'BIM-based Design Process',
                'year' => 3,
                'credits' => 14.0,
                'programs' => [
                    'Civil and Environmental Engineering',
                ],
            ],
            [
                'code' => '08KSIYE',
                'name' => 'Computer Networks',
                'year' => 3,
                'credits' => 8.0,
                'programs' => [
                    'Computer Engineering',
                ],
            ],
            [
                'code' => '01OIHWW',
                'name' => 'Digital Systems Electronics',
                'year' => 3,
                'credits' => 8.0,
                'programs' => [
                    'Electronic and Communications Engineering',
                ],
            ],
            [
                'code' => '05MYMWW',
                'name' => 'Digital Transmission',
                'year' => 3,
                'credits' => 8.0,
                'programs' => [
                    'Electronic and Communications Engineering',
                ],
            ],
            [
                'code' => '04LONXP',
                'name' => 'Electrical Machines',
                'year' => 3,
                'credits' => 8.0,
                'programs' => [
                    'Automotive Engineering',
                ],
            ],
            [
                'code' => '01QVUWW',
                'name' => 'Electromagnetic Waves and Antennas',
                'year' => 3,
                'credits' => 8.0,
                'programs' => [
                    'Electronic and Communications Engineering',
                ],
            ],
            [
                'code' => '03LTJWW',
                'name' => 'Electronic Measurements',
                'year' => 3,
                'credits' => 8.0,
                'programs' => [
                    'Electronic and Communications Engineering',
                ],
            ],
            [
                'code' => '03LNMYI',
                'name' => 'Fluid Mechanics',
                'year' => 3,
                'credits' => 6.0,
                'programs' => [
                    'Mechanical Engineering',
                ],
            ],
            [
                'code' => '01VTKWW',
                'name' => 'Fundamentals of Algorithm Programming and Machine Learning',
                'year' => 3,
                'credits' => 12.0,
                'programs' => [
                    'Electronic and Communications Engineering',
                ],
            ],
            [
                'code' => '01HLQYI',
                'name' => 'Fundamentals of Fluid Machinery',
                'year' => 3,
                'credits' => 10.0,
                'programs' => [
                    'Mechanical Engineering',
                ],
            ],
            [
                'code' => '02SXJYI',
                'name' => 'Fundamentals of Machine Design',
                'year' => 3,
                'credits' => 8.0,
                'programs' => [
                    'Automotive Engineering',
                    'Mechanical Engineering',
                ],
            ],
            [
                'code' => '03PZPWN',
                'name' => 'Geotechnics',
                'year' => 3,
                'credits' => 10.0,
                'programs' => [
                    'Civil and Environmental Engineering',
                ],
            ],
            [
                'code' => '01NLHYI',
                'name' => 'Industrial Plants and Safety',
                'year' => 3,
                'credits' => 10.0,
                'programs' => [
                    'Mechanical Engineering',
                ],
            ],
            [
                'code' => '01NLDYI',
                'name' => 'Introduction to Electrical Engineering / Electrical Machines',
                'year' => 3,
                'credits' => 10.0,
                'programs' => [
                    'Mechanical Engineering',
                ],
            ],
            [
                'code' => '01OFWXP',
                'name' => 'Manufacturing and Assembly Technologies',
                'year' => 3,
                'credits' => 10.0,
                'programs' => [
                    'Automotive Engineering',
                ],
            ],
            [
                'code' => '01NLEYI',
                'name' => 'Manufacturing Processes',
                'year' => 3,
                'credits' => 8.0,
                'programs' => [
                    'Mechanical Engineering',
                ],
            ],
            [
                'code' => '04LNLXP',
                'name' => 'Motor Vehicle Design',
                'year' => 3,
                'credits' => 8.0,
                'programs' => [
                    'Automotive Engineering',
                ],
            ],
            [
                'code' => '04JEYYE',
                'name' => 'Object Oriented Programming',
                'year' => 3,
                'credits' => 8.0,
                'programs' => [
                    'Computer Engineering',
                ],
            ],
            [
                'code' => '04JEZYE',
                'name' => 'Operating Systems',
                'year' => 3,
                'credits' => 6.0,
                'programs' => [
                    'Computer Engineering',
                ],
            ],
            [
                'code' => '01DRWWN',
                'name' => 'Roads, Railways and Airports / Water Infrastructures',
                'year' => 3,
                'credits' => 12.0,
                'programs' => [
                    'Civil and Environmental Engineering',
                ],
            ],
            [
                'code' => '02OGGYE',
                'name' => 'Signal Analysis and Processing',
                'year' => 3,
                'credits' => 8.0,
                'programs' => [
                    'Computer Engineering',
                ],
            ],
            [
                'code' => '01QVTWW',
                'name' => 'Signals and Systems',
                'year' => 3,
                'credits' => 10.0,
                'programs' => [
                    'Electronic and Communications Engineering',
                ],
            ],
            [
                'code' => '04PZQWN',
                'name' => 'Structural Engineering',
                'year' => 3,
                'credits' => 8.0,
                'programs' => [
                    'Civil and Environmental Engineering',
                ],
            ],
            [
                'code' => '01DRUWN',
                'name' => 'Sustainability and Circular Economy',
                'year' => 3,
                'credits' => 6.0,
                'programs' => [
                    'Civil and Environmental Engineering',
                ],
            ],
            [
                'code' => '01VRROA',
                'name' => 'Technologies for IoT Ecosystems',
                'year' => 3,
                'credits' => 8.0,
                'programs' => [
                    'Computer Engineering',
                ],
            ],
            [
                'code' => '01OFVXP',
                'name' => 'Thermal Machines',
                'year' => 3,
                'credits' => 8.0,
                'programs' => [
                    'Automotive Engineering',
                ],
            ],
            [
                'code' => '01DRTWN',
                'name' => 'Topographic and Geophysical Land Surveying',
                'year' => 3,
                'credits' => 10.0,
                'programs' => [
                    'Civil and Environmental Engineering',
                ],
            ],
        ];

        $savedCourses = [];
        foreach ($coursesData as $c) {
            $course = Course::updateOrCreate(
                ['course_code' => $c['code']],
                [
                    'name' => $c['name'],
                    'year' => $c['year'],
                    'credits' => $c['credits'],
                    'is_active' => true,
                ]
            );

            // Sync with associated programmes
            $programIds = [];
            foreach ($c['programs'] as $progName) {
                if (isset($programs[$progName])) {
                    $programIds[] = $programs[$progName]->id;
                }
            }
            $course->programs()->sync($programIds);

            $savedCourses[$c['name']] = $course;
        }

        // 6. Restore Resources and Files
        $notesType = ResourceType::where('code', 'notes')->first();
        $slidesType = ResourceType::where('code', 'slides')->first();
        $currentYear = AcademicYear::where('is_current', true)->first();

        // Resource 1: IOT - All slides
        $iotCourse = Course::where('course_code', '01VRROA')->first();
        if ($iotCourse) {
            $res1 = Resource::create([
                'course_id' => $iotCourse->id,
                'resource_type_id' => $slidesType?->id ?? 6,
                'academic_year_id' => $currentYear?->id,
                'title' => 'IOT - All slides',
                'description' => 'Complete lecture slides collection for IoT course.',
                'language' => 'en',
                'status' => 'approved',
                'submitted_by' => $user->id,
                'reviewed_by' => $user->id,
                'reviewed_at' => now(),
            ]);

            ResourceFile::create([
                'resource_id' => $res1->id,
                'original_filename' => 'Lectures.zip',
                'mime_type' => 'application/x-zip-compressed',
                'size_bytes' => 47170892,
                'sha256' => '8b1bbe8fa4b0250146cdfb0d00b502448d78ee02e545f5095733f7942a27798e',
                'local_path' => 'resources/QH16FVkzHVAS74XUAOes9KGXBQGaGGyO4EOnDXLT.zip',
                'drive_file_id' => '1ZKhM2ItZAObF7I1Ni9gr_W_n2JWhoZyJ',
                'telegram_file_id' => 'BQACAgQAAyEGAATpjWxDAAMZas2Nvoum0HfOuBL0FFy9cEp5WHgAAo0iAAJY7GlSfZjHeW5sexI9BA',
                'telegram_file_unique_id' => 'AgADjSIAAljsaVI',
            ]);

            // Resource 2: Arduino GPIO pins pdf
            $res2 = Resource::create([
                'course_id' => $iotCourse->id,
                'resource_type_id' => $slidesType?->id ?? 6,
                'academic_year_id' => $currentYear?->id,
                'title' => 'Arduino GPIO pins pdf',
                'description' => 'Arduino GPIO pinout reference.',
                'language' => 'en',
                'status' => 'approved',
                'submitted_by' => $user->id,
                'reviewed_by' => $user->id,
                'reviewed_at' => now(),
            ]);

            ResourceFile::create([
                'resource_id' => $res2->id,
                'original_filename' => 'All_pinout.pdf',
                'mime_type' => 'application/pdf',
                'size_bytes' => 501280,
                'sha256' => '5309d904a14bd70a21077e05971782ca2ab203a0ec03dcc7ed62cf4e95276ea3',
                'telegram_file_id' => 'BQACAgQAAxkBAAIBhmrP73UMZ63Qvdp81Z82zXfhT5ccAAJlIgAC0wd4Usc2U-UG8EzLPQQ',
                'telegram_file_unique_id' => 'AgADZSIAAtMHeFI',
            ]);
        }

        // Resource 3: Chemistry-2024-2025-All-Slides
        $chemCourse = Course::where('course_code', '06KWR')->first();
        if ($chemCourse) {
            $res3 = Resource::create([
                'course_id' => $chemCourse->id,
                'resource_type_id' => $notesType?->id ?? 3,
                'academic_year_id' => $currentYear?->id,
                'title' => 'Chemistry-2024-2025-All-Slides',
                'description' => 'Comprehensive slides for Chemistry.',
                'language' => 'en',
                'status' => 'approved',
                'submitted_by' => $user->id,
                'reviewed_by' => $user->id,
                'reviewed_at' => now(),
            ]);

            ResourceFile::create([
                'resource_id' => $res3->id,
                'original_filename' => 'Chemistry-2024-2025-All-Slides.zip',
                'mime_type' => 'application/zip',
                'size_bytes' => 71188872,
                'sha256' => '11d97513afc1d28292edf1c3e29c55705b4f55278d8028340ac414de9551e45a',
                'telegram_file_id' => 'BQACAgQAAxkBAAIBtmrQKCYv_c3iVB6dIIZXlaC7DvoAA5IeAALTB4BS4bY8nGiU2W49BA',
                'telegram_file_unique_id' => 'AgADkh4AAtMHgFI',
            ]);
        }
    }
}
