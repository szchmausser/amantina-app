<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('users.create');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $roles = $this->input('roles', []);
        $isAlumno = in_array('alumno', (array) $roles);

        return [
            'cedula' => [
                'required',
                'string',
                'max:20',
                Rule::unique('users')->whereNull('deleted_at'),
            ],
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users')->whereNull('deleted_at'),
            ],
            'phone' => [$isAlumno ? 'nullable' : 'required', 'string', 'max:20'],
            'address' => [$isAlumno ? 'nullable' : 'required', 'string', 'max:500'],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['string', 'exists:roles,name'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
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
            'roles.required' => 'Debes asignar al menos un rol.',
            'roles.*.exists' => 'Uno de los roles seleccionados no es válido.',
            'phone.required' => 'El teléfono es obligatorio para este tipo de usuario.',
            'address.required' => 'La dirección es obligatoria para este tipo de usuario.',
        ];
    }
}
