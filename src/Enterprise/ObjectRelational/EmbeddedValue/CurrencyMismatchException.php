<?php

declare(strict_types=1);

namespace DesignPatterns\Enterprise\ObjectRelational\EmbeddedValue;

use LogicException;

final class CurrencyMismatchException extends LogicException
{
    public static function between(Money $first, Money $second): self
    {
        return new self("Cannot add {$first->currency->value} to {$second->currency->value}");
    }
}
