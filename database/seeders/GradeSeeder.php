<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\Grade;
use Illuminate\Database\Seeder;

class GradeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // We assume an active Academic Year exists (created by AcademicYearSeeder)
        $year = AcademicYear::where('is_active', true)->first();

        if (! $year) {
            $this->command->warn('No active Academic Year found. Please run AcademicYearSeeder first.');

            return;
        }

        $grades = [
            ['name' => '1er Año', 'order' => 1],
            ['name' => '2do Año', 'order' => 2],
            ['name' => '3er Año', 'order' => 3],
            ['name' => '4to Año', 'order' => 4],
            ['name' => '5to Año', 'order' => 5],
        ];

        foreach ($grades as $gradeData) {
            Grade::updateOrCreate(
                ['academic_year_id' => $year->id, 'name' => $gradeData['name']],
                ['order' => $gradeData['order']]
            );
        }
    }
}
