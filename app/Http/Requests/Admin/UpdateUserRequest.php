<?php

namespace App\Http\Requests\Admin;

use App\Concerns\ProfileValidationRules;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateUserRequest extends FormRequest
{
    use ProfileValidationRules;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('user'));
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
        $roles = $this->input('roles', []);
        $isAlumno = in_array('alumno', (array) $roles);

        return array_merge(
            $this->profileRules($userId, ! $isAlumno),
            [
                'roles' => ['required', 'array', 'min:1'],
                'roles.*' => ['string', 'exists:roles,name'],
                'password' => ['nullable', 'string', 'min:8', 'confirmed'],
                'direct_permissions' => ['nullable', 'array'],
                'direct_permissions.*' => ['string', 'exists:permissions,name'],
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
