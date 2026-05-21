<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;

final class HistoryRequest extends FormRequest
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
        ];
    }

    public function fromDate(): Carbon
    {
        return $this->filled('from')
            ? Carbon::parse((string) $this->input('from'))->startOfDay()
            : Carbon::now()->subDays(30)->startOfDay();
    }

    public function toDate(): Carbon
    {
        return $this->filled('to')
            ? Carbon::parse((string) $this->input('to'))->endOfDay()
            : Carbon::now()->endOfDay();
    }
}
