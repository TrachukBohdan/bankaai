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
 * `/banks/api/organizationsList?locale=uk` returns:
 *   {
 *     "responseData": [
 *       { "id": ..., "slug": "privatbank", "title": "...", "longTitle": "...",
 *         "licenseNumber": "...", "licenseDate": "DD.MM.YYYY",
 *         "logo": [".../64.png", ".../128.png"],
 *         "legalAddress": "...", "site": "...", "phone": "...", "email": "..." },
 *       ...
 *     ],
 *     "responseOtherData": { "<slug>": { ... }, ... }
 *   }
 *
 * `/api/organization/v1/branches?slug={slug}&locale=uk` returns:
 *   {
 *     "data": [
 *       { "id": "...", "name": "City Name", "slug": "city-slug", "primary": false,
 *         "data": [{ "lat": "50.x", "lng": "27.x", "address": "...",
 *                    "branch_name": "...", "phone": "...", "primary": false }, ...] },
 *       ...
 *     ]
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

            // logo arrives as an array [64px-url, 128px-url]; pick the larger one.
            $logo = null;
            if (! empty($item['logo']) && is_array($item['logo'])) {
                $logo = (string) end($item['logo']);
            }

            $out[$slug] = new BankRecord(
                financeUaSlug: $slug,
                name: (string) ($item['title'] ?? $slug),
                legalName: isset($item['longTitle']) ? (string) $item['longTitle'] : null,
                logoUrl: $logo,
                website: isset($item['site']) ? (string) $item['site'] : null,
                phone: isset($item['phone']) ? (string) $item['phone'] : null,
                email: ! empty($item['email']) ? (string) $item['email'] : null,
                legalAddress: isset($item['legalAddress']) ? (string) $item['legalAddress'] : null,
                licenseNumber: isset($item['licenseNumber']) ? (string) $item['licenseNumber'] : null,
                licenseDate: $this->parseDate($item['licenseDate'] ?? null),
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
                'slug' => $financeUaSlug,
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
                    phone: isset($item['phone']) ? (string) $item['phone'] : null,
                    lat: $lat,
                    lng: $lng,
                    isPrimary: (bool) ($item['primary'] ?? false),
                );
            }
        }

        return $out;
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
