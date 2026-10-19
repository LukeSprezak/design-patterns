<?php

declare(strict_types=1);

namespace DesignPatterns\Enterprise\WebPresentation\ApplicationController;

enum IncidentAction: string
{
    case Dispatch = 'dispatch';
    case Arrive = 'arrive';
    case RequestBackup = 'request_backup';
    case Close = 'close';

    public function requiresUnits(): bool
    {
        return $this === self::Dispatch || $this === self::RequestBackup;
    }
}
