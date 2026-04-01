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
                Rule::unique('teacher_assignments', 'user_id')
                    ->where('academic_year_id', $this->academic_year_id)
                    ->where('section_id', $this->section_id)
                    ->whereNull('deleted_at'),
            ],
            'grade_id' => [
                'required',
                'exists:grades,id',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $grade = Grade::find($value);
                    if ($grade && (int) $grade->academic_year_id !== (int) $this->academic_year_id) {
                        $fail('El grado seleccionado no pertenece al año escolar indicado.');
                    }
                },
            ],
            'section_id' => [
                'required',
                'exists:sections,id',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $section = Section::find($value);
                    if ($section && (int) $section->grade_id !== (int) $this->grade_id) {
                        $fail('La sección seleccionada no pertenece al grado indicado.');
                    }
                },
            ],
        ];
    }
}
