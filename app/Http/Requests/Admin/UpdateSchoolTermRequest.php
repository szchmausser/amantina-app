<?php

namespace App\Http\Requests\Admin;

use App\Models\AcademicYear;
use App\Models\SchoolTerm;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSchoolTermRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('school_terms.edit') && $this->user()->can('academic_years.edit');
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $schoolTerm = $this->route('school_term');
        $academicYearId = $this->input('academic_year_id', $schoolTerm->academic_year_id);

        return [
            'academic_year_id' => ['required', 'exists:academic_years,id'],
            'term_type_id' => [
                'required',
                'exists:term_types,id',
                Rule::unique('school_terms', 'term_type_id')
                    ->where('academic_year_id', $academicYearId)
                    ->ignore($schoolTerm->id),
            ],
            'start_date' => [
                'required',
                'date',
                function (string $attribute, mixed $value, \Closure $fail) use ($academicYearId, $schoolTerm): void {
                    if (! $academicYearId) {
                        return;
                    }
                    $year = AcademicYear::find($academicYearId);
                    if ($year && $value < $year->start_date->format('Y-m-d')) {
                        $fail(__('La fecha de inicio del lapso no puede ser anterior al inicio del año escolar.'));
                    }

                    // Check for date overlap with existing terms (excluding current)
                    $this->checkDateOverlap($value, $this->end_date ?? $schoolTerm->end_date->format('Y-m-d'), $academicYearId, $schoolTerm->id, $fail);
                },
            ],
            'end_date' => [
                'required',
                'date',
                'after:start_date',
                function (string $attribute, mixed $value, \Closure $fail) use ($academicYearId): void {
                    if (! $academicYearId) {
                        return;
                    }
                    $year = AcademicYear::find($academicYearId);
                    if ($year && $value > $year->end_date->format('Y-m-d')) {
                        $fail(__('La fecha de fin del lapso no puede ser posterior al cierre del año escolar.'));
                    }
                },
            ],
        ];
    }

    /**
     * Check if the proposed date range overlaps with any existing term.
     */
    protected function checkDateOverlap(string $startDate, string $endDate, int $academicYearId, int $excludeId, \Closure $fail): void
    {
        $overlapping = SchoolTerm::where('academic_year_id', $academicYearId)
            ->where('id', '!=', $excludeId)
            ->where(function ($query) use ($startDate, $endDate) {
                // Overlap condition: existing.start <= new.end AND existing.end >= new.start
                $query->where('start_date', '<=', $endDate)
                    ->where('end_date', '>=', $startDate);
            })
            ->exists();

        if ($overlapping) {
            $fail(__('Las fechas del lapso se superponen con otro lapso existente en este año escolar.'));
        }
    }
}
