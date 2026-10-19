<?php

declare(strict_types=1);

namespace DesignPatterns\Enterprise\WebPresentation\Incident;

enum IncidentState: string
{
    case Reported = 'reported';
    case Dispatched = 'dispatched';
    case OnScene = 'on_scene';
    case Closed = 'closed';
}
