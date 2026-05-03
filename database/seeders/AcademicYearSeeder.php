<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\Grade;
use App\Models\Section;
use Illuminate\Database\Seeder;

class AcademicYearSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $year = AcademicYear::create([
            'name' => '2024-2025',
            'start_date' => '2024-09-01',
            'end_date' => '2025-07-15',
            'is_active' => true,
            'required_hours' => 120.00,
        ]);

        // Grados y Secciones
        $grades = [
            ['name' => '1er Año', 'order' => 1],
            ['name' => '2do Año', 'order' => 2],
            ['name' => '3er Año', 'order' => 3],
            ['name' => '4to Año', 'order' => 4],
            ['name' => '5to Año', 'order' => 5],
        ];

        foreach ($grades as $gradeData) {
            $grade = Grade::create([
                'academic_year_id' => $year->id,
                'name' => $gradeData['name'],
                'order' => $gradeData['order'],
            ]);

            Section::create([
                'academic_year_id' => $year->id,
                'grade_id' => $grade->id,
                'name' => 'A',
            ]);

            Section::create([
                'academic_year_id' => $year->id,
                'grade_id' => $grade->id,
                'name' => 'B',
            ]);
        }
    }
}
