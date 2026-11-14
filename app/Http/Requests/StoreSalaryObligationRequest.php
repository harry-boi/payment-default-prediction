<?php

namespace App\Http\Requests;

use App\Enums\EmployeeStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSalaryObligationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'employee_id' => [
                'required',
                'integer',
                Rule::exists('employees', 'id')
                    ->where('user_id', $this->user()?->id)
                    ->where('status', EmployeeStatus::Active->value),
            ],
            'amount' => [
                'required',
                'string',
                'regex:/^\d+(,\d{3})*(\.\d{1,2})?$/',
            ],
            'due_date' => ['required', 'date'],
            'payment_period' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * Custom messages for validation.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'employee_id.exists' => 'The selected employee is either invalid, inactive, or does not belong to your account.',
            'amount.regex' => 'The salary obligation amount must be a valid positive number (e.g. 70000 or 70,000.00).',
        ];
    }
}
