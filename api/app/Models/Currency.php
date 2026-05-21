<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $code
 * @property ?int $iso_numeric
 * @property string $name
 * @property ?string $symbol
 * @property bool $is_active
 * @property int $sort_order
 */
class Currency extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'iso_numeric',
        'name',
        'symbol',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
            'iso_numeric' => 'integer',
        ];
    }

    /** @return HasMany<ExchangeRate, $this> */
    public function exchangeRates(): HasMany
    {
        return $this->hasMany(ExchangeRate::class);
    }

    /** @param Builder<self> $query */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->orderBy('sort_order');
    }
}
