<?php

declare(strict_types=1);

namespace DesignPatterns\Enterprise\WebPresentation\FrontController;

use DesignPatterns\Enterprise\WebPresentation\Http\Request;
use DesignPatterns\Enterprise\WebPresentation\Http\Response;
use DesignPatterns\Enterprise\WebPresentation\Incident\Incident;
use DesignPatterns\Enterprise\WebPresentation\Incident\IncidentRegister;

final readonly class ListIncidentsCommand implements Command
{
    public function __construct(private IncidentRegister $incidents)
    {
    }

    #[\Override]
    public function execute(Request $request): Response
    {
        return Response::json(200, array_map(
            static fn (Incident $incident): array => [
                'id' => $incident->id,
                'address' => $incident->address,
                'state' => $incident->state->value,
            ],
            $this->incidents->all(),
        ));
    }
}
