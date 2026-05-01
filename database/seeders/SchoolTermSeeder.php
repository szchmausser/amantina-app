<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\SchoolTerm;
use App\Models\TermType;
use Illuminate\Database\Seeder;

class SchoolTermSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Ensure term_types exist
        $termTypes = [
            ['name' => 'Lapso 1', 'order' => 1, 'is_active' => true],
            ['name' => 'Lapso 2', 'order' => 2, 'is_active' => true],
            ['name' => 'Lapso 3', 'order' => 3, 'is_active' => true],
        ];

        foreach ($termTypes as $type) {
            TermType::updateOrCreate(['name' => $type['name']], $type);
        }

        // Get the active academic year
        $year = AcademicYear::where('is_active', true)->first();

        if (! $year) {
            $this->command->warn('No active academic year found. Skipping school terms creation.');

            return;
        }

        // Check if school terms already exist for this year
        if (SchoolTerm::where('academic_year_id', $year->id)->exists()) {
            $this->command->info('School terms already exist for academic year: '.$year->name);

            return;
        }

        // Create school terms for the active academic year
        $terms = [
            [
                'term_type_order' => 1,
                'start_date' => '2024-09-15',
                'end_date' => '2024-12-15',
            ],
            [
                'term_type_order' => 2,
                'start_date' => '2025-01-07',
                'end_date' => '2025-04-10',
            ],
            [
                'term_type_order' => 3,
                'start_date' => '2025-04-25',
                'end_date' => '2025-07-15',
            ],
        ];

        foreach ($terms as $termData) {
            $termType = TermType::where('order', $termData['term_type_order'])->first();

            if ($termType) {
                SchoolTerm::create([
                    'academic_year_id' => $year->id,
                    'term_type_id' => $termType->id,
                    'term_type_name' => $termType->name,
                    'start_date' => $termData['start_date'],
                    'end_date' => $termData['end_date'],
                ]);

                $this->command->info("Created school term: {$termType->name} for {$year->name}");
            }
        }
    }
}
