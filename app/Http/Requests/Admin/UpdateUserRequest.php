<?php

namespace App\Http\Requests\Admin;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('users.edit');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var User|string|int|null $routeUser */
        $routeUser = $this->route('user');
        $userId = $routeUser instanceof User ? $routeUser->id : $routeUser;
        $isAlumno = $this->input('role') === 'alumno';

        return [
            'cedula' => [
                'required',
                'string',
                'max:20',
                Rule::unique('users')->ignore($userId)->whereNull('deleted_at'),
            ],
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users')->ignore($userId)->whereNull('deleted_at'),
            ],
            'phone' => [$isAlumno ? 'nullable' : 'required', 'string', 'max:20'],
            'address' => [$isAlumno ? 'nullable' : 'required', 'string', 'max:500'],
            'role' => ['required', 'string', 'exists:roles,name'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'is_transfer' => ['nullable', 'boolean'],
            'institution_origin' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'cedula.unique' => 'Esta cédula ya está registrada.',
            'email.unique' => 'Este correo electrónico ya está registrado.',
            'role.exists' => 'El rol seleccionado no es válido.',
            'phone.required' => 'El teléfono es obligatorio para este tipo de usuario.',
            'address.required' => 'La dirección es obligatoria para este tipo de usuario.',
        ];
    }
}
