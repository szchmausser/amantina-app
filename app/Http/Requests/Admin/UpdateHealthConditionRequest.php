<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateHealthConditionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('health_conditions.edit');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $condition = $this->route('health_condition');

        return [
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('health_conditions', 'name')->ignore($condition),
            ],
            'is_active' => ['boolean'],
        ];
    }
}
