<?php

namespace App\Http\Requests;

use App\Models\SalaryObligation;
use App\Support\Money;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StorePaymentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        /** @var SalaryObligation|null $obligation */
        $obligation = $this->route('salaryObligation');

        if (! $obligation) {
            return false;
        }

        return $this->user() !== null && $obligation->employee->user_id === $this->user()->id;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'paid_at' => ['required', 'date_format:Y-m-d'],
            'amount_paid' => ['required'],
            'reference' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            /** @var SalaryObligation|null $obligation */
            $obligation = $this->route('salaryObligation');

            if (! $obligation) {
                return;
            }

            if ($obligation->isPaid()) {
                $validator->errors()->add('salary_obligation', 'This salary obligation has already been paid.');

                return;
            }

            try {
                $kobo = Money::toKobo((string) $this->input('amount_paid'));
                if ($kobo !== $obligation->amount) {
                    $validator->errors()->add(
                        'amount_paid',
                        "The payment amount must exactly equal the obligation amount ({$obligation->formatted_amount})."
                    );
                }
            } catch (\InvalidArgumentException $e) {
                $validator->errors()->add('amount_paid', $e->getMessage());
            }
        });
    }

    /**
     * Custom messages for validation errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'paid_at.required' => 'Please select the payment date.',
            'paid_at.date_format' => 'Payment date must be in YYYY-MM-DD format.',
            'amount_paid.required' => 'Amount paid is required.',
            'reference.max' => 'Payment reference or note must not exceed 255 characters.',
        ];
    }
}
