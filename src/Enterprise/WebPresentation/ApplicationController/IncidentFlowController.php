<?php

declare(strict_types=1);

namespace DesignPatterns\Enterprise\WebPresentation\ApplicationController;

use DesignPatterns\Enterprise\WebPresentation\Incident\Incident;
use DesignPatterns\Enterprise\WebPresentation\Incident\IncidentState;

final class IncidentFlowController
{
    /**
     * Runs the domain command for the action and returns the name of the view to show next.
     *
     * @param array<string, mixed> $input
     */
    #[\NoDiscard('the returned view must be rendered')]
    public function handle(Incident $incident, IncidentAction $action, array $input): string
    {
        return match ([$incident->state, $action]) {
            [IncidentState::Reported, IncidentAction::Dispatch] => $this->dispatch($incident, $input['units']),
            [IncidentState::Dispatched, IncidentAction::Arrive] => $this->arrive($incident),
            [IncidentState::OnScene, IncidentAction::RequestBackup] => $this->requestBackup($incident, $input['units']),
            [IncidentState::OnScene, IncidentAction::Close] => $this->close($incident),
            default => throw IllegalTransitionException::for($incident, $action),
        };
    }

    private function dispatch(Incident $incident, int $units): string
    {
        $incident->dispatch($units);

        return 'incident/en-route';
    }

    private function arrive(Incident $incident): string
    {
        $incident->arrive();

        return $incident->needsBackup ? 'incident/request-backup' : 'incident/firefighting';
    }

    private function requestBackup(Incident $incident, int $units): string
    {
        $incident->requestBackup($units);

        return 'incident/firefighting';
    }

    private function close(Incident $incident): string
    {
        $incident->close();

        return 'incident/report';
    }
}
