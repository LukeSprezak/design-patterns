<?php

declare(strict_types=1);

namespace DesignPatterns\Enterprise\DataSource\DataMapper;

use OutOfBoundsException;

final class RecordNotFoundException extends OutOfBoundsException
{
    public static function medication(string $ean): self
    {
        return new self("Medication {$ean} not found");
    }

    public static function prescription(int $prescriptionId): self
    {
        return new self("Prescription {$prescriptionId} not found");
    }
}
