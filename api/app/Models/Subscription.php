<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property ?int $bank_id
 * @property ?int $currency_id
 * @property float $threshold_pct
 */
class Subscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'bank_id',
        'currency_id',
        'threshold_pct',
    ];

    protected function casts(): array
    {
        return [
            'threshold_pct' => 'float',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
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

    /**
     * Returns true if this subscription matches the given rate-change target.
     * NULL on bank_id or currency_id means "any".
     */
    public function matches(?int $bankId, int $currencyId): bool
    {
        if ($this->bank_id !== null && $this->bank_id !== $bankId) {
            return false;
        }
        if ($this->currency_id !== null && $this->currency_id !== $currencyId) {
            return false;
        }

        return true;
    }
}
