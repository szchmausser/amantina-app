<?php

namespace App\Http\Requests\Admin;

use App\Models\Grade;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSectionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('sections.create') && $this->user()->can('academic_years.edit');
    }

    public function rules(): array
    {
        return [
            'academic_year_id' => ['required', 'exists:academic_years,id'],
            'grade_id' => [
                'required',
                'exists:grades,id',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $grade = Grade::find($value);
                    if ($grade && (int) $grade->academic_year_id !== (int) $this->academic_year_id) {
                        $fail(__('El grado seleccionado no pertenece al año académico indicado.'));
                    }
                },
            ],
            'name' => [
                'required',
                'string',
                'max:10',
                Rule::unique('sections', 'name')
                    ->where('academic_year_id', $this->academic_year_id)
                    ->where('grade_id', $this->grade_id),
            ],
        ];
    }
}
