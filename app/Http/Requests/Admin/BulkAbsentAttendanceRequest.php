<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class BulkAbsentAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('attendances.create');
    }

    public function rules(): array
    {
        return [
            'student_ids' => ['required', 'array', 'min:1'],
            'student_ids.*' => ['integer', 'exists:users,id'],
        ];
    }
}
