<?php

namespace App\Http\Requests\Admin;

use App\Models\AcademicYear;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSchoolTermRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('school_terms.create') && $this->user()->can('academic_years.edit');
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'academic_year_id' => ['required', 'exists:academic_years,id'],
            'term_number' => [
                'required',
                'integer',
                'between:1,3',
                Rule::unique('school_terms', 'term_number')
                    ->where('academic_year_id', $this->academic_year_id),
            ],
            'start_date' => [
                'required',
                'date',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (! $this->academic_year_id) {
                        return;
                    }
                    $year = AcademicYear::find($this->academic_year_id);
                    if ($year && $value < $year->start_date->format('Y-m-d')) {
                        $fail(__('La fecha de inicio del lapso no puede ser anterior al inicio del año escolar.'));
                    }
                },
            ],
            'end_date' => [
                'required',
                'date',
                'after:start_date',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (! $this->academic_year_id) {
                        return;
                    }
                    $year = AcademicYear::find($this->academic_year_id);
                    if ($year && $value > $year->end_date->format('Y-m-d')) {
                        $fail(__('La fecha de fin del lapso no puede ser posterior al cierre del año escolar.'));
                    }
                },
            ],
        ];
    }
}
