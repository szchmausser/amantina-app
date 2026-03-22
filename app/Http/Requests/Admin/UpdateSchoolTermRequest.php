<?php

namespace App\Http\Requests\Admin;

use App\Models\AcademicYear;
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
            'term_number' => [
                'required',
                'integer',
                'between:1,3',
                Rule::unique('school_terms', 'term_number')
                    ->where('academic_year_id', $academicYearId)
                    ->ignore($schoolTerm->id),
            ],
            'start_date' => [
                'required',
                'date',
                function (string $attribute, mixed $value, \Closure $fail) use ($academicYearId): void {
                    if (! $academicYearId) {
                        return;
                    }
                    $year = AcademicYear::find($academicYearId);
                    if ($year && $value < $year->start_date->format('Y-m-d')) {
                        $fail(__('La fecha de inicio del lapso no puede ser anterior al inicio del año escolar.'));
                    }
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
}
