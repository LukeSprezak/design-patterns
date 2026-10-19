<?php

declare(strict_types=1);

namespace DesignPatterns\Enterprise\WebPresentation\ApplicationController;

use DesignPatterns\Enterprise\WebPresentation\FrontController\Command;
use DesignPatterns\Enterprise\WebPresentation\Http\Request;
use DesignPatterns\Enterprise\WebPresentation\Http\Response;
use DesignPatterns\Enterprise\WebPresentation\Incident\IncidentRegister;

final readonly class IncidentFlowCommand implements Command
{
    public function __construct(
        private IncidentRegister $incidents,
        private IncidentFlowController $flowController,
    ) {
    }

    #[\Override]
    public function execute(Request $request): Response
    {
        $incident = $this->incidents->find((int) $request->pathParameters['id']);
        $action = IncidentAction::tryFrom($request->pathParameters['action']);
        if ($incident === null || $action === null) {
            return Response::json(404, ['error' => 'unknown incident or action']);
        }
        if ($action->requiresUnits() && !is_int($request->body['units'] ?? null)) {
            return Response::json(422, ['error' => "action {$action->value} requires an integer number of units"]);
        }

        try {
            $view = $this->flowController->handle($incident, $action, $request->body);
        } catch (IllegalTransitionException $illegalTransition) {
            return Response::json(409, ['error' => $illegalTransition->getMessage()]);
        }

        return Response::json(200, ['view' => $view, 'state' => $incident->state->value]);
    }
}
