<?php

declare(strict_types=1);

namespace DesignPatterns\Tests\Enterprise\WebPresentation\ApplicationController;

use DesignPatterns\Enterprise\WebPresentation\ApplicationController\IncidentAction;
use DesignPatterns\Enterprise\WebPresentation\ApplicationController\IncidentFlowCommand;
use DesignPatterns\Enterprise\WebPresentation\ApplicationController\IncidentFlowController;
use DesignPatterns\Enterprise\WebPresentation\Http\Request;
use DesignPatterns\Enterprise\WebPresentation\Incident\IncidentRegister;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(IncidentFlowCommand::class)]
#[CoversClass(IncidentAction::class)]
#[CoversClass(IncidentRegister::class)]
final class IncidentFlowCommandTest extends TestCase
{
    private IncidentFlowCommand $command;

    protected function setUp(): void
    {
        $incidents = new IncidentRegister();
        $incidents->report('Main Street 5', requiredUnits: 2);
        $this->command = new IncidentFlowCommand($incidents, new IncidentFlowController());
    }

    #[Test]
    public function returnsNotFoundForUnknownIncidentOrAction(): void
    {
        self::assertSame(404, $this->command->execute($this->request(id: '99', action: 'dispatch'))->statusCode);
        self::assertSame(404, $this->command->execute($this->request(id: '1', action: 'evacuate'))->statusCode);
    }

    #[Test]
    public function rejectsActionThatNeedsUnitsWhenUnitsAreMissingOrNotInteger(): void
    {
        self::assertSame(422, $this->command->execute($this->request(id: '1', action: 'dispatch'))->statusCode);
        self::assertSame(422, $this->command->execute($this->request(id: '1', action: 'request_backup', body: ['units' => 'two']))->statusCode);
    }

    #[Test]
    public function returnsConflictWhenActionIsNotAllowedInCurrentState(): void
    {
        $response = $this->command->execute($this->request(id: '1', action: 'close'));

        self::assertSame(409, $response->statusCode);
    }

    #[Test]
    public function returnsNextViewAndNewStateAfterAllowedAction(): void
    {
        $response = $this->command->execute($this->request(id: '1', action: 'dispatch', body: ['units' => 2]));

        self::assertSame(200, $response->statusCode);
        self::assertSame(['view' => 'incident/en-route', 'state' => 'dispatched'], json_decode($response->body, associative: true));
    }

    /** @param array<string, mixed> $body */
    private function request(string $id, string $action, array $body = []): Request
    {
        return new Request('POST', "/incidents/{$id}/{$action}", body: $body)
            ->withPathParameters(['id' => $id, 'action' => $action]);
    }
}
