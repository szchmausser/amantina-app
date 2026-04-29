<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\File;

class UpdateExternalHourRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('external_hours.edit');
    }

    public function rules(): array
    {
        return [
            'user_id' => ['required', 'exists:users,id'],
            'period' => ['required', 'string', 'max:50'],
            'hours' => ['required', 'numeric', 'min:0.5', 'max:9999.99'],
            'institution_name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'documents' => ['nullable', 'array'],
            'documents.*' => [
                'nullable',
                File::types(['pdf', 'jpg', 'jpeg', 'png', 'webp'])
                    ->max(5 * 1024),
            ],
            'delete_media_ids' => ['nullable', 'array'],
            'delete_media_ids.*' => ['integer', 'exists:media,id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'user_id.required' => 'El estudiante es obligatorio.',
            'user_id.exists' => 'El estudiante seleccionado no existe.',
            'period.required' => 'El período es obligatorio.',
            'period.string' => 'El período debe ser texto.',
            'period.max' => 'El período no puede superar 50 caracteres.',
            'hours.required' => 'Las horas son obligatorias.',
            'hours.numeric' => 'Las horas deben ser un valor numérico.',
            'hours.min' => 'Las horas deben ser al menos 0.5.',
            'hours.max' => 'Las horas no pueden superar 9999.99.',
            'institution_name.required' => 'El nombre de la institución es obligatorio.',
            'institution_name.string' => 'El nombre de la institución debe ser texto.',
            'institution_name.max' => 'El nombre de la institución no puede superar 255 caracteres.',
            'description.string' => 'La descripción debe ser texto.',
            'description.max' => 'La descripción no puede superar 1000 caracteres.',
            'documents.array' => 'Los documentos deben ser un arreglo de archivos.',
            'documents.*.mimes' => 'Cada documento debe ser PDF, JPG, PNG o WEBP.',
            'documents.*.max' => 'Cada documento no puede superar 5 MB.',
            'delete_media_ids.array' => 'Los documentos a eliminar deben ser un arreglo.',
            'delete_media_ids.*.integer' => 'El identificador de documento debe ser un número entero.',
            'delete_media_ids.*.exists' => 'Uno de los documentos a eliminar no existe.',
        ];
    }
}
