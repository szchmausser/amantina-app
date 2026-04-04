<?php

namespace App\Http\Requests\Admin;

use App\Models\AcademicYear;
use App\Models\SchoolTerm;
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
            'term_type_id' => [
                'required',
                'exists:term_types,id',
                Rule::unique('school_terms', 'term_type_id')
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

                    // Check for date overlap with existing terms
                    $this->checkDateOverlap($value, $this->end_date, $fail);
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

    /**
     * Check if the proposed date range overlaps with any existing term.
     */
    protected function checkDateOverlap(string $startDate, ?string $endDate, \Closure $fail): void
    {
        if (! $this->academic_year_id || ! $endDate) {
            return;
        }

        $overlapping = SchoolTerm::where('academic_year_id', $this->academic_year_id)
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
