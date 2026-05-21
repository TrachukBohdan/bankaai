<?php

declare(strict_types=1);

namespace App\Services\Http;

use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\PendingRequest;

/**
 * Thin DRY wrapper around Laravel's HTTP client with sensible production-ish
 * defaults: short timeout, JSON accept, retries with backoff, friendly UA.
 * Subclasses provide the base URL; everything else is shared.
 */
abstract class HttpJsonClient
{
    public function __construct(protected HttpFactory $http) {}

    abstract protected function baseUrl(): string;

    protected function userAgent(): string
    {
        return 'BankaAi/1.0 (+https://github.com/BankaAi)';
    }

    protected function timeoutSeconds(): int
    {
        return 15;
    }

    protected function client(): PendingRequest
    {
        return $this->http
            ->baseUrl($this->baseUrl())
            ->acceptJson()
            ->timeout($this->timeoutSeconds())
            ->retry(3, 250, throw: false)
            ->withUserAgent($this->userAgent());
    }
}
