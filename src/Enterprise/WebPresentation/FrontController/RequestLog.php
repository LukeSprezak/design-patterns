<?php

declare(strict_types=1);

namespace DesignPatterns\Enterprise\WebPresentation\FrontController;

use DesignPatterns\Enterprise\WebPresentation\Http\Request;
use DesignPatterns\Enterprise\WebPresentation\Http\Response;

final class RequestLog
{
    /** @var list<string> */
    public private(set) array $entries = [];

    public function record(Request $request, Response $response): void
    {
        $this->entries[] = "{$request->method} {$request->path} -> {$response->statusCode}";
    }
}
