<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\Grade;
use App\Models\SchoolTerm;
use App\Models\Section;
use App\Models\TermType;
use Illuminate\Database\Seeder;

class AcademicYearSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Ensure term_types exist
        $termTypes = [
            ['name' => 'Lapso 1', 'order' => 1],
            ['name' => 'Lapso 2', 'order' => 2],
            ['name' => 'Lapso 3', 'order' => 3],
        ];

        foreach ($termTypes as $type) {
            TermType::updateOrCreate(['name' => $type['name']], $type);
        }

        $year = AcademicYear::create([
            'name' => '2024-2025',
            'start_date' => '2024-09-01',
            'end_date' => '2025-07-15',
            'is_active' => true,
            'required_hours' => 600.00,
        ]);

        // Lapsos
        SchoolTerm::create([
            'academic_year_id' => $year->id,
            'term_type_id' => TermType::where('order', 1)->value('id'),
            'start_date' => '2024-09-15',
            'end_date' => '2024-12-15',
        ]);
        SchoolTerm::create([
            'academic_year_id' => $year->id,
            'term_type_id' => TermType::where('order', 2)->value('id'),
            'start_date' => '2025-01-07',
            'end_date' => '2025-04-10',
        ]);
        SchoolTerm::create([
            'academic_year_id' => $year->id,
            'term_type_id' => TermType::where('order', 3)->value('id'),
            'start_date' => '2025-04-25',
            'end_date' => '2025-07-15',
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
