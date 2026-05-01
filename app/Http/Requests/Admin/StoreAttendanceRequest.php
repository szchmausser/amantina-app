<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreAttendanceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('attendances.create');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        // For bulk registration
        if ($this->has('student_ids')) {
            return [
                'field_session_id' => ['sometimes', 'integer', 'exists:field_sessions,id'],
                'student_ids' => ['required', 'array', 'min:1'],
                'student_ids.*' => ['integer', 'exists:users,id'],
                'attended' => ['nullable', 'boolean'],
                'notes' => ['nullable', 'string', 'max:1000'],
            ];
        }

        // For single registration
        return [
            'field_session_id' => ['sometimes', 'integer', 'exists:field_sessions,id'],
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'attended' => ['required', 'boolean'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * Prepare the data for validation.
     * If field_session_id is not in the request, get it from the route parameter.
     */
    protected function prepareForValidation(): void
    {
        if (! $this->has('field_session_id') && $this->route('fieldSession')) {
            $this->merge([
                'field_session_id' => $this->route('fieldSession')->id,
            ]);
        }
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'user_id.required' => 'Debe seleccionar un estudiante.',
            'student_ids.required' => 'Debe seleccionar al menos un estudiante.',
            'field_session_id.required' => 'La jornada de campo es obligatoria.',
            'academic_year_id.required' => 'El año escolar es obligatorio.',
        ];
    }
}
