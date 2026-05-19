<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSectionDefinitionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('section_definitions.edit');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $definition = $this->route('section_definition');

        return [
            'name' => [
                'required',
                'string',
                'max:1',
                'regex:/^[A-Z]$/',
                Rule::unique('section_definitions', 'name')->ignore($definition)->withoutTrashed(),
            ],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * Get the custom validation messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.regex' => 'El nombre de la definición debe ser una letra mayúscula (A-Z).',
        ];
    }
}
