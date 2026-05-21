<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\DTO\Coordinates;
use App\Http\Requests\NearestBranchRequest;
use App\Http\Resources\BranchResource;
use App\Models\Bank;
use App\Services\Branches\NearestBranchFinder;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class BranchController extends Controller
{
    public function __construct(private NearestBranchFinder $finder) {}

    public function nearest(NearestBranchRequest $request): AnonymousResourceCollection
    {
        $coords = new Coordinates(
            (float) $request->input('lat'),
            (float) $request->input('lng'),
        );

        $bankId = null;
        if ($request->filled('bank')) {
            $bankId = Bank::where('slug', $request->input('bank'))->value('id');
        }

        $branches = $this->finder->find(
            $coords,
            (int) $request->input('limit', 10),
            $bankId ? (int) $bankId : null,
        );

        return BranchResource::collection($branches);
    }
}
