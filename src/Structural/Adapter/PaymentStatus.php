<?php

declare(strict_types=1);

namespace DesignPatterns\Structural\Adapter;

enum PaymentStatus
{
    case Paid;
    case Pending;
    case Declined;
}
