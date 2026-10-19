<?php

declare(strict_types=1);

namespace DesignPatterns\Enterprise\DomainLogic\TableModule;

enum CrewType: string
{
    case Specialist = 'S';
    case Basic = 'P';
}
