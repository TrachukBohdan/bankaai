<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $slug
 * @property ?string $minfin_slug
 * @property ?string $finance_ua_slug
 * @property string $name
 * @property ?string $legal_name
 * @property ?string $description
 * @property ?string $logo_url
 * @property ?string $website
 * @property ?string $phone
 * @property ?string $email
 * @property ?string $legal_address
 * @property ?string $license_number
 * @property ?Carbon $license_date
 * @property ?float $rating
 * @property bool $is_active
 * @property ?Carbon $last_synced_at
 */
class Bank extends Model
{
    use HasFactory;

    protected $fillable = [
        'slug',
        'minfin_slug',
        'finance_ua_slug',
        'name',
        'legal_name',
        'description',
        'logo_url',
        'website',
        'phone',
        'email',
        'legal_address',
        'license_number',
        'license_date',
        'rating',
        'is_active',
        'last_synced_at',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'rating' => 'decimal:1',
            'license_date' => 'date',
            'last_synced_at' => 'datetime',
        ];
    }

    /** @return HasMany<Branch, $this> */
    public function branches(): HasMany
    {
        return $this->hasMany(Branch::class);
    }

    /** @return HasMany<ExchangeRate, $this> */
    public function exchangeRates(): HasMany
    {
        return $this->hasMany(ExchangeRate::class);
    }

    /** @param Builder<self> $query */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
