<?php

namespace App\Http\Requests;

use App\Enums\EmployeeStatus;
use App\Enums\SalaryFrequency;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEmployeeRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'job_title' => ['nullable', 'string', 'max:255'],
            'salary_amount' => [
                'required',
                'string',
                'regex:/^\d+(,\d{3})*(\.\d{1,2})?$/',
            ],
            'salary_frequency' => ['required', Rule::enum(SalaryFrequency::class)],
            'status' => ['required', Rule::enum(EmployeeStatus::class)],
        ];
    }

    /**
     * Custom validation messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'salary_amount.regex' => 'The salary amount must be a valid positive number (e.g. 50000 or 50,000.00).',
        ];
    }
}
