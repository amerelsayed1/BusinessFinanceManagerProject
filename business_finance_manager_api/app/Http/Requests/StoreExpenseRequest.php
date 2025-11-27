<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Account;

class StoreExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'account_id' => 'required|exists:accounts,id',
            'category_id' => 'nullable|exists:expense_categories,id',
            'amount' => 'required|numeric|min:0.01',
            'date' => 'required|date',
            'description' => 'nullable|string|max:1000',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Verify account belongs to authenticated user
            $account = Account::find($this->account_id);
            if ($account && $account->user_id !== auth()->id()) {
                $validator->errors()->add('account_id', 'The selected account does not belong to you.');
            }

            // Verify sufficient balance
            if ($account && $account->balance < $this->amount) {
                $validator->errors()->add('amount', 'Insufficient account balance.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'account_id.required' => 'Please select an account.',
            'account_id.exists' => 'The selected account does not exist.',
            'amount.required' => 'Please enter an amount.',
            'amount.min' => 'Amount must be at least 0.01.',
            'date.required' => 'Please select a date.',
        ];
    }
}
