<?php

namespace App\Http\Requests\Admin;

use App\Models\Grade;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSectionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('sections.edit') && $this->user()->can('academic_years.edit');
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $section = $this->route('section');

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
        ];
    }
}
