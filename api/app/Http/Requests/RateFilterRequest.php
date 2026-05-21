<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class RateFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'bank'     => ['sometimes', 'string'],
            'banks'    => ['sometimes', 'array'],
            'banks.*'  => ['string'],
            'currency' => ['sometimes', 'string', 'size:3'],
            'market'   => ['sometimes', 'string', 'in:cash,card,official'],
            'source'   => ['sometimes', 'string', 'in:minfin,nbu'],
        ];
    }

    /** @return array<string, mixed> */
    public function filters(): array
    {
        $filters = [];

        $banks = [];
        if ($this->filled('bank')) {
            $banks[] = (string) $this->input('bank');
        }
        if ($this->filled('banks')) {
            $banks = [...$banks, ...array_map('strval', (array) $this->input('banks'))];
        }
        if ($banks !== []) {
            $filters['bank_slugs'] = array_values(array_unique($banks));
        }

        if ($this->filled('currency')) {
            $filters['currency_codes'] = [strtoupper((string) $this->input('currency'))];
        }
        if ($this->filled('market')) {
            $filters['market'] = (string) $this->input('market');
        }
        if ($this->filled('source')) {
            $filters['source'] = (string) $this->input('source');
        }

        return $filters;
    }
}
