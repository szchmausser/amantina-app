<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\File;

class UpdateStudentHealthRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('student_health.edit');
    }

    public function rules(): array
    {
        return [
            'health_condition_id' => ['required', 'exists:health_conditions,id'],
            'received_by' => ['required', 'exists:users,id'],
            'received_at' => ['required', 'date'],
            'received_at_location' => ['nullable', 'string', 'max:100'],
            'observations' => ['nullable', 'string'],
            'documents' => ['nullable', 'array'],
            'documents.*' => [
                'nullable',
                File::types(['pdf', 'jpg', 'jpeg', 'png', 'gif', 'webp'])
                    ->max(5 * 1024),
            ],
            'document_descriptions' => ['nullable', 'array'],
            'delete_media_ids' => ['nullable', 'array'],
            'delete_media_ids.*' => ['integer', 'exists:media,id'],
        ];
    }
}
