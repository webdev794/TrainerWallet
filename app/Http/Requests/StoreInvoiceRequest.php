<?php

namespace App\Http\Requests;

use App\Enums\PaymentGatewayType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isTrainer() ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'client_id' => [
                'required',
                Rule::exists('clients', 'id')->where('trainer_id', $this->user()->id),
            ],
            'due_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'discount_total' => ['nullable', 'numeric', 'min:0', 'max:100000000'],
            'tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'allowed_methods' => ['array'],
            'allowed_methods.*' => [Rule::enum(PaymentGatewayType::class)],
            'items' => ['required', 'array', 'min:1'],
            'items.*.description' => ['required', 'string', 'max:255'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01', 'max:100000'],
            'items.*.unit_amount' => ['required', 'numeric', 'min:0', 'max:100000000'],
            'items.*.training_session_id' => [
                'nullable',
                Rule::exists('training_sessions', 'id')->where('trainer_id', $this->user()->id),
            ],
        ];
    }
}
