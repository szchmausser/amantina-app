<?php

namespace App\Http\Requests\Admin;

use App\Models\FieldSessionStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateFieldSessionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('field_sessions.edit');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $cancelledStatusId = FieldSessionStatus::where('name', 'cancelled')->value('id');

        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'school_term_id' => ['nullable', 'integer', 'exists:school_terms,id'],
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'activity_name' => ['nullable', 'string', 'max:100'],
            'location_name' => ['nullable', 'string', 'max:100'],
            'start_datetime' => ['required', 'date', 'before:end_datetime'],
            'end_datetime' => ['required', 'date', 'after:start_datetime'],
            'status_id' => ['required', 'integer', 'exists:field_session_statuses,id'],
            'cancellation_reason' => [
                Rule::requiredIf((int) $this->status_id === $cancelledStatusId),
                'nullable',
                'string',
                'max:1000',
            ],
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'start_datetime.before' => 'La fecha de inicio debe ser anterior a la fecha de fin.',
            'end_datetime.after' => 'La fecha de fin debe ser posterior a la fecha de inicio.',
            'cancellation_reason.required' => 'El motivo de cancelación es obligatorio cuando el estado es "Cancelada".',
        ];
    }
}
