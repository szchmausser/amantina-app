<?php

namespace App\Http\Requests\Admin;

use App\Models\AcademicYear;
use App\Models\Grade;
use App\Models\Section;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTeacherAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('assignments.create');
    }

    public function rules(): array
    {
        $activeYear = AcademicYear::active()->first();

        return [
            'academic_year_id' => [
                'required',
                'exists:academic_years,id',
                function (string $attribute, mixed $value, \Closure $fail) use ($activeYear): void {
                    if (! $activeYear || (int) $value !== $activeYear->id) {
                        $fail('Solo se pueden realizar asignaciones en el año escolar activo.');
                    }
                },
            ],
            'user_id' => [
                'required',
                'exists:users,id',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $user = User::find($value);
                    if (! $user || ! $user->hasRole('profesor')) {
                        $fail('El usuario seleccionado no tiene el rol de profesor.');
                    }
                },
            ],
            'section_ids' => [
                'present',
                'array',
            ],
            'section_ids.*' => [
                'exists:sections,id',
            ],
        ];
    }
}
