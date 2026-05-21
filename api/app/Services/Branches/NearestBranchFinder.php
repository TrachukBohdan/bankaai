<?php

declare(strict_types=1);

namespace App\Services\Branches;

use App\DTO\Coordinates;
use App\Models\Branch;
use Illuminate\Database\Eloquent\Collection;

/**
 * SRP: nearest-branches query backed by MySQL 8's spatial index.
 *
 * ST_Distance_Sphere returns meters. We use the index for ordering only and
 * eager-load the bank relation so the controller can shape the response.
 */
final class NearestBranchFinder
{
    public function find(Coordinates $origin, int $limit = 10, ?int $bankId = null): Collection
    {
        // ST_GeomFromText expects POINT(lng lat) (X then Y) per SRID 4326.
        $point = sprintf(
            "ST_GeomFromText('POINT(%F %F)', 4326)",
            $origin->lng,
            $origin->lat,
        );

        $query = Branch::query()
            ->with('bank')
            ->select('branches.*')
            ->selectRaw("ST_Distance_Sphere(coordinates, {$point}) AS distance_m")
            ->orderBy('distance_m')
            ->limit($limit);

        if ($bankId !== null) {
            $query->where('bank_id', $bankId);
        }

        return $query->get();
    }
}
