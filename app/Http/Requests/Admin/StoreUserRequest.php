<?php

namespace App\Http\Requests\Admin;

use App\Concerns\ProfileValidationRules;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
{
    use ProfileValidationRules;

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

        return array_merge(
            $this->profileRules(null, ! $isAlumno),
            [
                'roles' => ['required', 'array', 'min:1'],
                'roles.*' => ['string', 'exists:roles,name'],
                'password' => ['required', 'string', 'min:8', 'confirmed'],
            ]
        );
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
