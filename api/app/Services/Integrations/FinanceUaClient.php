<?php

declare(strict_types=1);

namespace App\Services\Integrations;

use App\Contracts\BankDirectory;
use App\Contracts\BranchDirectory;
use App\DTO\BankRecord;
use App\DTO\BranchRecord;
use App\Services\Http\HttpJsonClient;
use DateTimeImmutable;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Adapter for finance.ua's directory + branches APIs.
 *
 * `GET /banks/api/organizationsList?locale=uk` returns:
 *   {
 *     "responseData": [
 *       {
 *         "slug": "privatbank",
 *         "title": "ПриватБанк",
 *         "longTitle": "Акціонерне товариство …",
 *         "ratingBank": 4.9,
 *         "licenseNumber": "…",
 *         "licenseDate": "05.10.2011",
 *         "logo": ["…/64.png", "…/128.png"],
 *         "squareLogo": ["…"],
 *         "legalAddress": "…",
 *         "site": "https://…",
 *         "phone": "3700",
 *         "email": "…"
 *       },
 *       …
 *     ],
 *     "responseOtherData": { … }
 *   }
 */
final class FinanceUaClient extends HttpJsonClient implements BankDirectory, BranchDirectory
{
    protected function baseUrl(): string
    {
        return 'https://finance.ua/';
    }

    public function fetchBanks(array $slugs): array
    {
        $response = $this->client()->get('banks/api/organizationsList', [
            'locale' => 'uk',
        ]);
        if (! $response->successful()) {
            Log::warning('finance.ua organizationsList failed', ['status' => $response->status()]);

            return [];
        }

        $items = $response->json('responseData') ?? [];
        if (! is_array($items)) {
            return [];
        }

        $wanted = array_fill_keys($slugs, true);
        $out = [];
        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }
            $slug = isset($item['slug']) ? (string) $item['slug'] : null;
            if ($slug === null || ! isset($wanted[$slug])) {
                continue;
            }

            $out[$slug] = new BankRecord(
                financeUaSlug: $slug,
                name: (string) ($item['title'] ?? $slug),
                legalName: $this->stringOrNull($item['longTitle'] ?? null),
                logoUrl: $this->resolveLogoUrl($item),
                website: $this->stringOrNull($item['site'] ?? null),
                phone: $this->stringOrNull($item['phone'] ?? null),
                email: $this->stringOrNull($item['email'] ?? null),
                legalAddress: $this->stringOrNull($item['legalAddress'] ?? null),
                licenseNumber: $this->stringOrNull($item['licenseNumber'] ?? null),
                licenseDate: $this->parseDate($item['licenseDate'] ?? null),
                rating: $this->parseRating($item['ratingBank'] ?? null),
            );
        }

        return $out;
    }

    public function fetchBranches(string $financeUaSlug): array
    {
        $response = $this->client()->get('api/organization/v1/branches', [
            'slug' => $financeUaSlug,
            'locale' => 'uk',
        ]);
        if (! $response->successful()) {
            Log::warning('finance.ua branches failed', [
                'status' => $response->status(),
                'slug'   => $financeUaSlug,
            ]);

            return [];
        }

        $cities = $response->json('data') ?? [];
        if (! is_array($cities)) {
            return [];
        }

        $out = [];
        foreach ($cities as $city) {
            if (! is_array($city)) {
                continue;
            }
            $cityName = isset($city['name']) ? (string) $city['name'] : null;
            $cityId = isset($city['id']) ? (string) $city['id'] : null;
            $items = $city['data'] ?? [];
            if (! is_array($items)) {
                continue;
            }

            foreach ($items as $i => $item) {
                if (! is_array($item)) {
                    continue;
                }
                $lat = isset($item['lat']) ? (float) $item['lat'] : null;
                $lng = isset($item['lng']) ? (float) $item['lng'] : null;
                if ($lat === null || $lng === null || ($lat === 0.0 && $lng === 0.0)) {
                    continue;
                }
                $externalId = $cityId !== null ? "{$cityId}-{$i}" : null;

                $out[] = new BranchRecord(
                    financeUaSlug: $financeUaSlug,
                    externalId: $externalId,
                    name: (string) ($item['branch_name'] ?? $cityName ?? 'Branch'),
                    city: $cityName,
                    address: (string) ($item['address'] ?? ''),
                    phone: $this->stringOrNull($item['phone'] ?? null),
                    lat: $lat,
                    lng: $lng,
                    isPrimary: (bool) ($item['primary'] ?? false),
                );
            }
        }

        return $out;
    }

    /** @param array<string, mixed> $item */
    private function resolveLogoUrl(array $item): ?string
    {
        foreach (['logo', 'squareLogo'] as $key) {
            if (! empty($item[$key]) && is_array($item[$key])) {
                $url = (string) end($item[$key]);

                return $this->stringOrNull($url);
            }
        }

        return null;
    }

    private function stringOrNull(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }
        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }

    private function parseRating(mixed $raw): ?float
    {
        if ($raw === null || $raw === '') {
            return null;
        }
        if (! is_numeric($raw)) {
            return null;
        }
        $rating = (float) $raw;

        return $rating > 0 ? round($rating, 1) : null;
    }

    private function parseDate(mixed $raw): ?DateTimeImmutable
    {
        if (! is_string($raw) || $raw === '') {
            return null;
        }
        $parsed = DateTimeImmutable::createFromFormat('d.m.Y', $raw);
        if ($parsed !== false) {
            return $parsed->setTime(0, 0);
        }
        try {
            return new DateTimeImmutable($raw);
        } catch (Throwable) {
            return null;
        }
    }
}
