<?php

declare(strict_types=1);

namespace DesignPatterns\Tests\Enterprise\WebPresentation\FrontController;

use DesignPatterns\Enterprise\WebPresentation\FrontController\ListIncidentsCommand;
use DesignPatterns\Enterprise\WebPresentation\FrontController\Route;
use DesignPatterns\Enterprise\WebPresentation\Http\Request;
use DesignPatterns\Enterprise\WebPresentation\Incident\IncidentRegister;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(Route::class)]
final class RouteTest extends TestCase
{
    private Route $route;

    protected function setUp(): void
    {
        $this->route = new Route('POST', '/incidents/{id}/{action}', new ListIncidentsCommand(new IncidentRegister()));
    }

    #[Test]
    public function extractsPathParametersFromMatchingRequest(): void
    {
        self::assertSame(
            ['id' => '7', 'action' => 'dispatch'],
            $this->route->match(new Request('POST', '/incidents/7/dispatch')),
        );
    }

    #[Test]
    public function doesNotMatchDifferentMethodOrPath(): void
    {
        self::assertNull($this->route->match(new Request('GET', '/incidents/7/dispatch')));
        self::assertNull($this->route->match(new Request('POST', '/incidents/7')));
        self::assertNull($this->route->match(new Request('POST', '/incidents/7/dispatch/now')));
    }
}
