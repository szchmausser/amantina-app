<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class BulkAssignHoursRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('attendances.create');
    }

    public function rules(): array
    {
        return [
            'data' => ['required', 'array', 'min:1'],
            'data.*.user_id' => ['required', 'integer', 'exists:users,id'],
            'data.*.activity_category_id' => ['required', 'integer', 'exists:activity_categories,id'],
            'data.*.hours' => ['required', 'numeric', 'min:0.01', 'max:24'],
            'data.*.notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'data.required' => 'Debe proporcionar al menos un estudiante.',
            'data.*.hours.min' => 'Las horas deben ser mayor a 0.',
            'data.*.hours.max' => 'Las horas no pueden exceder 24 horas.',
        ];
    }
}
