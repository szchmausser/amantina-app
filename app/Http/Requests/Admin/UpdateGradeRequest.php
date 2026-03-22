<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateGradeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('grades.edit') && $this->user()->can('academic_years.edit');
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $grade = $this->route('grade');
        $academicYearId = $this->input('academic_year_id', $grade->academic_year_id);

        return [
            'academic_year_id' => ['required', 'exists:academic_years,id'],
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('grades', 'name')
                    ->where('academic_year_id', $academicYearId)
                    ->ignore($grade->id),
            ],
            'order' => [
                'required',
                'integer',
                'min:1',
                Rule::unique('grades', 'order')
                    ->where('academic_year_id', $academicYearId)
                    ->ignore($grade->id),
            ],
        ];
    }
}
