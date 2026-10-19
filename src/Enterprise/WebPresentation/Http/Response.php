<?php

declare(strict_types=1);

namespace DesignPatterns\Enterprise\WebPresentation\Http;

final readonly class Response
{
    public function __construct(
        public int $statusCode,
        public string $body,
    ) {
    }

    /** @param array<array-key, mixed> $payload */
    public static function json(int $statusCode, array $payload): self
    {
        return new self($statusCode, json_encode($payload, JSON_THROW_ON_ERROR));
    }
}
