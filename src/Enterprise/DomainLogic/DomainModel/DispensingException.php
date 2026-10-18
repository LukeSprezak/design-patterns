<?php

declare(strict_types=1);

namespace DesignPatterns\Enterprise\DomainLogic\DomainModel;

use DateTimeImmutable;
use DesignPatterns\Enterprise\DomainLogic\DomainModel\Medication\Medication;
use DesignPatterns\Enterprise\DomainLogic\DomainModel\Prescription\Prescription;
use DomainException;

final class DispensingException extends DomainException
{
    public static function alreadyDispensed(Prescription $prescription, DateTimeImmutable $dispensedAt): self
    {
        return new self("Prescription {$prescription->id} was already dispensed at {$dispensedAt->format('Y-m-d H:i:s')}");
    }

    public static function expired(Prescription $prescription): self
    {
        return new self("Prescription {$prescription->id} expired on {$prescription->validUntil->format('Y-m-d')}");
    }

    public static function insufficientStock(Medication $medication, int $requested): self
    {
        return new self("{$medication->name}: requested {$requested}, only {$medication->stock} in stock");
    }
}
