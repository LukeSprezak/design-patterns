<?php

declare(strict_types=1);

namespace DesignPatterns\Enterprise\WebPresentation\Http;

final readonly class Request
{
    /**
     * @param array<string, string> $headers
     * @param array<string, mixed> $body
     * @param array<string, string> $pathParameters
     */
    public function __construct(
        public string $method,
        public string $path,
        public array $headers = [],
        public array $body = [],
        public array $pathParameters = [],
    ) {
    }

    /** @param array<string, string> $pathParameters */
    public function withPathParameters(array $pathParameters): self
    {
        return clone($this, ['pathParameters' => $pathParameters]);
    }
}
