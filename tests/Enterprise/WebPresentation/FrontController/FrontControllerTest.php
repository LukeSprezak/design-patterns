<?php

declare(strict_types=1);

namespace DesignPatterns\Tests\Enterprise\WebPresentation\FrontController;

use DesignPatterns\Enterprise\WebPresentation\ApplicationController\IncidentFlowCommand;
use DesignPatterns\Enterprise\WebPresentation\ApplicationController\IncidentFlowController;
use DesignPatterns\Enterprise\WebPresentation\FrontController\FrontController;
use DesignPatterns\Enterprise\WebPresentation\FrontController\ListIncidentsCommand;
use DesignPatterns\Enterprise\WebPresentation\FrontController\ReportIncidentCommand;
use DesignPatterns\Enterprise\WebPresentation\FrontController\RequestLog;
use DesignPatterns\Enterprise\WebPresentation\FrontController\Route;
use DesignPatterns\Enterprise\WebPresentation\Http\Request;
use DesignPatterns\Enterprise\WebPresentation\Http\Response;
use DesignPatterns\Enterprise\WebPresentation\Incident\IncidentRegister;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(FrontController::class)]
#[CoversClass(RequestLog::class)]
#[CoversClass(ListIncidentsCommand::class)]
#[CoversClass(ReportIncidentCommand::class)]
#[CoversClass(Request::class)]
#[CoversClass(Response::class)]
final class FrontControllerTest extends TestCase
{
    private const string DISPATCHER_TOKEN = 'station-42-secret';

    private RequestLog $requestLog;
    private FrontController $frontController;

    protected function setUp(): void
    {
        $incidents = new IncidentRegister();
        $this->requestLog = new RequestLog();
        $this->frontController = new FrontController(
            routes: [
                new Route('GET', '/incidents', new ListIncidentsCommand($incidents)),
                new Route('POST', '/incidents', new ReportIncidentCommand($incidents)),
                new Route('POST', '/incidents/{id}/{action}', new IncidentFlowCommand($incidents, new IncidentFlowController())),
            ],
            dispatcherToken: self::DISPATCHER_TOKEN,
            requestLog: $this->requestLog,
        );
    }

    #[Test]
    public function rejectsRequestWithoutValidDispatcherToken(): void
    {
        $response = $this->frontController->handle(new Request('GET', '/incidents', headers: ['X-Dispatcher-Token' => 'wrong']));

        self::assertSame(401, $response->statusCode);
    }

    #[Test]
    public function returnsNotFoundForUnknownRoute(): void
    {
        $response = $this->frontController->handle($this->authenticated('GET', '/hydrants'));

        self::assertSame(404, $response->statusCode);
    }

    #[Test]
    public function routesRequestsToCommandsThatShareOneIncidentRegister(): void
    {
        $reportResponse = $this->frontController->handle(
            $this->authenticated('POST', '/incidents', ['address' => 'Main Street 5', 'requiredUnits' => 2]),
        );
        $listResponse = $this->frontController->handle($this->authenticated('GET', '/incidents'));

        self::assertSame(201, $reportResponse->statusCode);
        self::assertSame(
            [['id' => 1, 'address' => 'Main Street 5', 'state' => 'reported']],
            json_decode($listResponse->body, true, 512, JSON_THROW_ON_ERROR),
        );
    }

    #[Test]
    public function passesPathParametersToCommand(): void
    {
        (void) $this->frontController->handle($this->authenticated('POST', '/incidents', ['address' => 'Main Street 5', 'requiredUnits' => 2]));

        $response = $this->frontController->handle($this->authenticated('POST', '/incidents/1/dispatch', ['units' => 2]));

        self::assertSame(200, $response->statusCode);
        self::assertSame(['view' => 'incident/en-route', 'state' => 'dispatched'], json_decode($response->body, associative: true));
    }

    #[Test]
    public function rejectsIncompleteIncidentReport(): void
    {
        $response = $this->frontController->handle($this->authenticated('POST', '/incidents', ['address' => 'Main Street 5']));

        self::assertSame(422, $response->statusCode);
    }

    #[Test]
    public function logsEveryHandledRequestWithItsStatus(): void
    {
        (void) $this->frontController->handle(new Request('GET', '/incidents'));
        (void) $this->frontController->handle($this->authenticated('GET', '/incidents'));
        (void) $this->frontController->handle($this->authenticated('GET', '/hydrants'));

        self::assertSame(
            ['GET /incidents -> 401', 'GET /incidents -> 200', 'GET /hydrants -> 404'],
            $this->requestLog->entries,
        );
    }

    /** @param array<string, mixed> $body */
    private function authenticated(string $method, string $path, array $body = []): Request
    {
        return new Request($method, $path, headers: ['X-Dispatcher-Token' => self::DISPATCHER_TOKEN], body: $body);
    }
}
