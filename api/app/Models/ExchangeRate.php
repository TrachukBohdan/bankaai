<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property ?int $bank_id
 * @property int $currency_id
 * @property string $market
 * @property string $source
 * @property ?float $buy
 * @property ?float $sell
 * @property Carbon $rate_at
 * @property Carbon $fetched_at
 */
class ExchangeRate extends Model
{
    use HasFactory;

    public const SOURCE_MINFIN = 'minfin';

    public const SOURCE_NBU = 'nbu';

    public const MARKET_CASH = 'cash';

    public const MARKET_CARD = 'card';

    public const MARKET_OFFICIAL = 'official';

    protected $fillable = [
        'bank_id',
        'currency_id',
        'market',
        'source',
        'buy',
        'sell',
        'rate_at',
        'fetched_at',
    ];

    protected function casts(): array
    {
        return [
            'buy' => 'float',
            'sell' => 'float',
            'rate_at' => 'datetime',
            'fetched_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Bank, $this> */
    public function bank(): BelongsTo
    {
        return $this->belongsTo(Bank::class);
    }

    /** @return BelongsTo<Currency, $this> */
    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    /** @param Builder<self> $query */
    public function scopeNbu(Builder $query): Builder
    {
        return $query->where('source', self::SOURCE_NBU);
    }

    /** @param Builder<self> $query */
    public function scopeMinfin(Builder $query): Builder
    {
        return $query->where('source', self::SOURCE_MINFIN);
    }
}
