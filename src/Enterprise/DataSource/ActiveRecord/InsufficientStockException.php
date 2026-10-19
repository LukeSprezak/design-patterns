<?php

declare(strict_types=1);

namespace DesignPatterns\Enterprise\DataSource\ActiveRecord;

use DomainException;

final class InsufficientStockException extends DomainException
{
    public static function for(Medication $medication, int $requestedQuantity): self
    {
        return new self(
            "Cannot dispense {$requestedQuantity} x {$medication->name}: only {$medication->stock} in stock",
        );
    }
}
