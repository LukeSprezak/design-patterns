<?php

declare(strict_types=1);

namespace DesignPatterns\Enterprise\WebPresentation\ApplicationController;

use DesignPatterns\Enterprise\WebPresentation\Incident\Incident;
use DomainException;

final class IllegalTransitionException extends DomainException
{
    public static function for(Incident $incident, IncidentAction $action): self
    {
        return new self("Incident {$incident->id} is {$incident->state->value}, action {$action->value} is not allowed");
    }
}
