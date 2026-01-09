<?php

namespace App\Http\Requests;

use App\Enums\PaymentMethod;
use Illuminate\Foundation\Http\FormRequest;

class StorePaymentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->isCashier();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'sale_id' => ['required', 'exists:sales,id'],
            'method' => ['required', 'in:'.implode(',', array_column(PaymentMethod::cases(), 'value'))],
            'amount' => ['required', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'sale_id.required' => 'Sale ID is required.',
            'sale_id.exists' => 'Selected sale does not exist.',
            'method.required' => 'Payment method is required.',
            'method.in' => 'Payment method must be one of: '.implode(', ', array_column(PaymentMethod::cases(), 'value')),
            'amount.required' => 'Payment amount is required.',
            'amount.numeric' => 'Payment amount must be a numeric value.',
            'amount.min' => 'Payment amount cannot be negative.',
        ];
    }
}
