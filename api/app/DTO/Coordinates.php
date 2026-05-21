<?php

declare(strict_types=1);

namespace App\DTO;

use InvalidArgumentException;

final readonly class Coordinates
{
    public function __construct(public float $lat, public float $lng)
    {
        if ($lat < -90.0 || $lat > 90.0) {
            throw new InvalidArgumentException("Latitude {$lat} is out of range");
        }
        if ($lng < -180.0 || $lng > 180.0) {
            throw new InvalidArgumentException("Longitude {$lng} is out of range");
        }
    }
}
