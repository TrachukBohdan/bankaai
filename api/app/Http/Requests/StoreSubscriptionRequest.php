<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Bank;
use App\Models\Currency;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

final class StoreSubscriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'bank_slug'      => ['sometimes', 'nullable', 'string', 'exists:banks,slug'],
            'currency_code'  => ['sometimes', 'nullable', 'string', 'size:3', 'exists:currencies,code'],
            'threshold_pct'  => ['sometimes', 'numeric', 'min:0.1', 'max:100'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v): void {
            $user = $this->user();
            if ($user === null) {
                return;
            }

            $bankId = $this->filled('bank_slug')
                ? Bank::where('slug', $this->input('bank_slug'))->value('id')
                : null;
            $currencyId = $this->filled('currency_code')
                ? Currency::where('code', strtoupper((string) $this->input('currency_code')))->value('id')
                : null;

            $exists = $user->subscriptions()
                ->where('bank_id', $bankId)
                ->where('currency_id', $currencyId)
                ->exists();

            if ($exists) {
                $v->errors()->add('subscription', 'You already have this subscription.');
            }
        });
    }
}
