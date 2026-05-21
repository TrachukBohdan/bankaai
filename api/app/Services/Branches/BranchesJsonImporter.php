<?php

declare(strict_types=1);

namespace App\Services\Branches;

use App\Models\Bank;
use App\Models\Branch;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use RuntimeException;

/**
 * Imports branch rows from database/data/branches.json (no HTTP).
 */
final class BranchesJsonImporter
{
    public function __construct(
        private string $dataPath = '',
    ) {
        $this->dataPath = $dataPath !== ''
            ? $dataPath
            : database_path('data/branches.json');
    }

    public function import(bool $replaceExisting = true): int
    {
        if (! File::isFile($this->dataPath)) {
            throw new RuntimeException("Branches JSON not found: {$this->dataPath}");
        }

        $payload = json_decode(File::get($this->dataPath), true);
        if (! is_array($payload)) {
            throw new RuntimeException('Invalid branches JSON: root must be an object.');
        }

        $rows = $payload['branches'] ?? null;
        if (! is_array($rows) || $rows === []) {
            throw new RuntimeException('Invalid branches JSON: missing non-empty "branches" array.');
        }

        $bankIds = Bank::query()
            ->whereNotNull('finance_ua_slug')
            ->pluck('id', 'finance_ua_slug')
            ->all();

        $now = Carbon::now();
        $total = 0;

        if ($replaceExisting) {
            Branch::query()->delete();
        }

        $grouped = [];
        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }
            $slug = (string) ($row['finance_ua_slug'] ?? '');
            if ($slug === '' || ! isset($bankIds[$slug])) {
                continue;
            }
            $grouped[$slug][] = $row;
        }

        foreach ($grouped as $slug => $bankRows) {
            $bankId = $bankIds[$slug];

            DB::transaction(function () use ($bankId, $bankRows, $now, &$total): void {
                foreach ($bankRows as $row) {
                    $lat = isset($row['lat']) ? (float) $row['lat'] : null;
                    $lng = isset($row['lng']) ? (float) $row['lng'] : null;
                    if ($lat === null || $lng === null || ($lat === 0.0 && $lng === 0.0)) {
                        continue;
                    }

                    $address = trim((string) ($row['address'] ?? ''));
                    if ($address === '') {
                        continue;
                    }

                    Branch::create([
                        'bank_id' => $bankId,
                        'external_id' => $row['external_id'] ?? null,
                        'name' => (string) ($row['name'] ?? 'Branch'),
                        'city' => $row['city'] ?? null,
                        'address' => $address,
                        'phone' => $row['phone'] ?? null,
                        'lat' => $lat,
                        'lng' => $lng,
                        'is_primary' => (bool) ($row['is_primary'] ?? false),
                        'last_synced_at' => $now,
                    ]);
                    $total++;
                }
            });
        }

        return $total;
    }

    /** @return array{count: int} */
    public function meta(): array
    {
        if (! File::isFile($this->dataPath)) {
            return ['count' => 0];
        }

        $payload = json_decode(File::get($this->dataPath), true);
        $rows = is_array($payload) ? ($payload['branches'] ?? []) : [];

        return ['count' => is_array($rows) ? count($rows) : 0];
    }
}
