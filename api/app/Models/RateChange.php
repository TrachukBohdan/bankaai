<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property ?int $bank_id
 * @property int $currency_id
 * @property string $market
 * @property string $source
 * @property string $side
 * @property float $previous_value
 * @property float $new_value
 * @property float $delta_pct
 * @property float $threshold_pct
 * @property Carbon $observed_at
 */
class RateChange extends Model
{
    use HasFactory;

    public const SIDE_BUY = 'buy';

    public const SIDE_SELL = 'sell';

    protected $fillable = [
        'bank_id',
        'currency_id',
        'market',
        'source',
        'side',
        'previous_value',
        'new_value',
        'delta_pct',
        'threshold_pct',
        'observed_at',
    ];

    protected function casts(): array
    {
        return [
            'previous_value' => 'float',
            'new_value' => 'float',
            'delta_pct' => 'float',
            'threshold_pct' => 'float',
            'observed_at' => 'datetime',
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
}
