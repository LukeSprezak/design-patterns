<?php

declare(strict_types=1);

namespace DesignPatterns\Tests\Enterprise\WebPresentation\ApplicationController;

use DesignPatterns\Enterprise\WebPresentation\ApplicationController\IllegalTransitionException;
use DesignPatterns\Enterprise\WebPresentation\ApplicationController\IncidentAction;
use DesignPatterns\Enterprise\WebPresentation\ApplicationController\IncidentFlowController;
use DesignPatterns\Enterprise\WebPresentation\Incident\Incident;
use DesignPatterns\Enterprise\WebPresentation\Incident\IncidentState;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(IncidentFlowController::class)]
#[CoversClass(IllegalTransitionException::class)]
#[CoversClass(Incident::class)]
final class IncidentFlowControllerTest extends TestCase
{
    private IncidentFlowController $flowController;
    private Incident $incident;

    protected function setUp(): void
    {
        $this->flowController = new IncidentFlowController();
        $this->incident = new Incident(id: 1, address: 'Main Street 5', requiredUnits: 2);
    }

    #[Test]
    public function dispatchingMovesIncidentToDispatchedAndShowsEnRouteView(): void
    {
        $view = $this->flowController->handle($this->incident, IncidentAction::Dispatch, ['units' => 2]);

        self::assertSame('incident/en-route', $view);
        self::assertSame(IncidentState::Dispatched, $this->incident->state);
    }

    #[Test]
    public function arrivingWithEnoughUnitsShowsFirefightingView(): void
    {
        (void) $this->flowController->handle($this->incident, IncidentAction::Dispatch, ['units' => 2]);

        $view = $this->flowController->handle($this->incident, IncidentAction::Arrive, []);

        self::assertSame('incident/firefighting', $view);
        self::assertSame(IncidentState::OnScene, $this->incident->state);
    }

    #[Test]
    public function arrivingWithTooFewUnitsShowsRequestBackupView(): void
    {
        (void) $this->flowController->handle($this->incident, IncidentAction::Dispatch, ['units' => 1]);

        $view = $this->flowController->handle($this->incident, IncidentAction::Arrive, []);

        self::assertSame('incident/request-backup', $view);
    }

    #[Test]
    public function backupReturnsToFirefightingViewAndClosingShowsReport(): void
    {
        (void) $this->flowController->handle($this->incident, IncidentAction::Dispatch, ['units' => 1]);
        (void) $this->flowController->handle($this->incident, IncidentAction::Arrive, []);

        self::assertSame('incident/firefighting', $this->flowController->handle($this->incident, IncidentAction::RequestBackup, ['units' => 1]));
        self::assertSame('incident/report', $this->flowController->handle($this->incident, IncidentAction::Close, []));
        self::assertSame(IncidentState::Closed, $this->incident->state);
    }

    #[Test]
    public function rejectsActionNotAllowedInCurrentState(): void
    {
        $this->expectException(IllegalTransitionException::class);
        $this->expectExceptionMessage('close');

        (void) $this->flowController->handle($this->incident, IncidentAction::Close, []);
    }
}
