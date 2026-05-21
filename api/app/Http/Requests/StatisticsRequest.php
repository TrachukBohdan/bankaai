<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;

final class StatisticsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'from'     => ['sometimes', 'date'],
            'to'       => ['sometimes', 'date', 'after_or_equal:from'],
            'bank'     => ['sometimes', 'string'],
            'currency' => ['sometimes', 'string', 'size:3'],
            'market'   => ['sometimes', 'string', 'in:cash,card,official'],
            'source'   => ['sometimes', 'string', 'in:minfin,nbu'],
        ];
    }

    /** @return array<string, mixed> */
    public function filters(): array
    {
        $from = $this->filled('from')
            ? Carbon::parse((string) $this->input('from'))->startOfDay()
            : Carbon::now()->subDays(30)->startOfDay();
        $to = $this->filled('to')
            ? Carbon::parse((string) $this->input('to'))->endOfDay()
            : Carbon::now()->endOfDay();

        $filters = ['from' => $from, 'to' => $to];

        if ($this->filled('bank')) {
            $filters['bank_slugs'] = [(string) $this->input('bank')];
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
