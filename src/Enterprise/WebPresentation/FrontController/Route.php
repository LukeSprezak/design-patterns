<?php

declare(strict_types=1);

namespace DesignPatterns\Enterprise\WebPresentation\FrontController;

use DesignPatterns\Enterprise\WebPresentation\Http\Request;

final readonly class Route
{
    public function __construct(
        public string $method,
        public string $pathPattern,
        public Command $command,
    ) {
    }

    /** @return array<string, string>|null path parameters, or null when the request does not match */
    public function match(Request $request): ?array
    {
        if ($request->method !== $this->method) {
            return null;
        }

        $regularExpression = '#^' . preg_replace('/\{(\w+)}/', '(?<$1>[^/]+)', $this->pathPattern) . '$#';
        if (preg_match($regularExpression, $request->path, $matches) !== 1) {
            return null;
        }

        return array_filter($matches, is_string(...), ARRAY_FILTER_USE_KEY);
    }
}
