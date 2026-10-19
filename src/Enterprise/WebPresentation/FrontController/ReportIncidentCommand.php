<?php

declare(strict_types=1);

namespace DesignPatterns\Enterprise\WebPresentation\FrontController;

use DesignPatterns\Enterprise\WebPresentation\Http\Request;
use DesignPatterns\Enterprise\WebPresentation\Http\Response;
use DesignPatterns\Enterprise\WebPresentation\Incident\IncidentRegister;

final readonly class ReportIncidentCommand implements Command
{
    public function __construct(private IncidentRegister $incidents)
    {
    }

    #[\Override]
    public function execute(Request $request): Response
    {
        $address = $request->body['address'] ?? null;
        $requiredUnits = $request->body['requiredUnits'] ?? null;
        if (!is_string($address) || !is_int($requiredUnits)) {
            return Response::json(422, ['error' => 'address and requiredUnits are required']);
        }

        $incident = $this->incidents->report($address, $requiredUnits);

        return Response::json(201, ['id' => $incident->id]);
    }
}
