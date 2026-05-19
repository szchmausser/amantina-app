<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreGradeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('grades.create') && $this->user()->can('academic_years.edit');
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'academic_year_id' => ['required', 'exists:academic_years,id'],
            'grade_definition_id' => ['required', 'exists:grade_definitions,id'],
            'order' => [
                'required',
                'integer',
                'min:1',
                Rule::unique('grades', 'order')
                    ->where('academic_year_id', $this->academic_year_id)
                    ->withoutTrashed(),
            ],
        ];
    }
}
