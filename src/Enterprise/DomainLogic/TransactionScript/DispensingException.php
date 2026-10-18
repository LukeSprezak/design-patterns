<?php

declare(strict_types=1);

namespace DesignPatterns\Enterprise\DomainLogic\TransactionScript;

use DomainException;

final class DispensingException extends DomainException
{
    public static function prescriptionNotFound(int $prescriptionId): self
    {
        return new self("Prescription {$prescriptionId} not found");
    }

    public static function medicationNotFound(string $ean): self
    {
        return new self("Medication with EAN {$ean} not found");
    }

    public static function alreadyDispensed(int $prescriptionId, string $dispensedAt): self
    {
        return new self("Prescription {$prescriptionId} was already dispensed at {$dispensedAt}");
    }

    public static function expired(int $prescriptionId, string $validUntil): self
    {
        return new self("Prescription {$prescriptionId} expired on {$validUntil}");
    }

    public static function insufficientStock(string $medicationName, int $requested, int $available): self
    {
        return new self("{$medicationName}: requested {$requested}, only {$available} in stock");
    }
}
