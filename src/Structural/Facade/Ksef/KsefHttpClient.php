<?php

declare(strict_types=1);

namespace DesignPatterns\Structural\Facade\Ksef;

interface KsefHttpClient
{
    /**
     * @param array<string, mixed> $body
     * @return array<string, mixed> decoded JSON response
     */
    public function post(string $path, array $body, ?string $bearerToken): array;

    /** @return array<string, mixed> decoded JSON response */
    public function get(string $path, string $bearerToken): array;
}
