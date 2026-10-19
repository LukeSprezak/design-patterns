<?php

declare(strict_types=1);

namespace DesignPatterns\Enterprise\DomainLogic\TableModule;

enum DispatchOutcome: string
{
    case Transported = 'transported';
    case TreatedOnSite = 'treated_on_site';
    case Cancelled = 'cancelled';

    public function isBillable(): bool
    {
        return $this !== self::Cancelled;
    }
}
