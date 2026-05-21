<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $bank_id
 * @property ?string $external_id
 * @property string $name
 * @property ?string $city
 * @property string $address
 * @property ?string $phone
 * @property float $lat
 * @property float $lng
 * @property bool $is_primary
 * @property ?Carbon $last_synced_at
 */
class Branch extends Model
{
    use HasFactory;

    protected $fillable = [
        'bank_id',
        'external_id',
        'name',
        'city',
        'address',
        'phone',
        'lat',
        'lng',
        'is_primary',
        'last_synced_at',
    ];

    protected function casts(): array
    {
        return [
            'lat' => 'float',
            'lng' => 'float',
            'is_primary' => 'boolean',
            'last_synced_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Bank, $this> */
    public function bank(): BelongsTo
    {
        return $this->belongsTo(Bank::class);
    }
}
