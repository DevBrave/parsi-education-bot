<?php

namespace Database\Seeders;

use App\Models\Course;
use Illuminate\Database\Seeder;

class CourseYearSeeder extends Seeder
{
    public function run(): void
    {
        // Year 1
        $year1Codes = [
            '06KWR',
            '07JCJ',
            '04KWQ',
            '03KXT',
            '04KXV',
            '02OJPYE',
            '04KXXYI-07KXXXP',
            '01PZGWN',
            '04LTMWW',
        ];

        // Year 2
        $year2Codes = [
            '01URNYE',
            '03LMA',
            '05LSLWW',
            '01QVQWW',
            '02KTMYE',
            '01GYBYE',
            '01URLYE',
            '04OIGWW',
            '02LTHWW',
            '01DRSWN',
            '05LNMWN',
            '04OFTXP',
            '01NLGYI',
            '01NLAYI',
            '01RKWYE',
            '02KXU',
            '07KXUYE',
            '03KXUWW',
            '02LSIWW',
            '01UROYE',
            '01VUGYI',
            '05KXW',
            '15KXWWW',
            '02NLCWN',
            '01NLMYI',
            '07LLQWN',
            '01DRRWN',
        ];

        // Year 3
        $year3Codes = [
            '02MZGWW',
            '01TUMWW',
            '06LSLYE',
            '04LSLXP',
            '01DRVWN',
            '08KSIYE',
            '01OIHWW',
            '05MYMWW',
            '04LONXP',
            '01QVUWW',
            '03LTJWW',
            '03LNMYI',
            '01VTKWW',
            '01HLQYI',
            '02SXJYI',
            '03PZPWN',
            '01NLHYI',
            '01NLDYI',
            '01OFWXP',
            '01NLEYI',
            '04LNLXP',
            '04JEYYE',
            '04JEZYE',
            '01DRWWN',
            '02OGGYE',
            '01QVTWW',
            '04PZQWN',
            '01DRUWN',
            '01VRROA',
            '01OFVXP',
            '01DRTWN',
        ];

        Course::whereIn('course_code', $year1Codes)->update(['year' => 1]);
        Course::whereIn('course_code', $year2Codes)->update(['year' => 2]);
        Course::whereIn('course_code', $year3Codes)->update(['year' => 3]);
    }
}
