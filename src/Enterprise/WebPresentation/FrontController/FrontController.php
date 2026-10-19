<?php

declare(strict_types=1);

namespace DesignPatterns\Enterprise\WebPresentation\FrontController;

use DesignPatterns\Enterprise\WebPresentation\Http\Request;
use DesignPatterns\Enterprise\WebPresentation\Http\Response;

final readonly class FrontController
{
    private const string TOKEN_HEADER = 'X-Dispatcher-Token';

    /** @param list<Route> $routes */
    public function __construct(
        private array $routes,
        #[\SensitiveParameter]
        private string $dispatcherToken,
        private RequestLog $requestLog,
    ) {
    }

    public function handle(Request $request): Response
    {
        $response = $this->dispatch($request);
        $this->requestLog->record($request, $response);

        return $response;
    }

    private function dispatch(Request $request): Response
    {
        if (($request->headers[self::TOKEN_HEADER] ?? null) !== $this->dispatcherToken) {
            return Response::json(401, ['error' => 'invalid dispatcher token']);
        }

        foreach ($this->routes as $route) {
            $pathParameters = $route->match($request);
            if ($pathParameters !== null) {
                return $route->command->execute($request->withPathParameters($pathParameters));
            }
        }

        return Response::json(404, ['error' => "no route for {$request->method} {$request->path}"]);
    }
}
