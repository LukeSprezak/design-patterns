<?php

declare(strict_types=1);

namespace DesignPatterns\Creational\Builder;

use DesignPatterns\Creational\Builder\Medication\Medication;
use DomainException;

final class InvalidPrescriptionException extends DomainException
{
    public static function missing(string $requiredPart): self
    {
        return new self("Prescription requires a {$requiredPart}");
    }

    public static function noMedications(): self
    {
        return new self('Prescription requires at least one medication');
    }

    public static function tooManyMedications(int $medicationCount, int $limit): self
    {
        return new self("{$medicationCount} medications exceed the limit of {$limit}");
    }

    public static function controlledSubstanceWithoutPermit(Medication $medication): self
    {
        return new self("{$medication->name} is a controlled substance and requires a permit number");
    }

    public static function validityTooLong(int $requestedDays, int $allowedDays): self
    {
        return new self("Validity of {$requestedDays} days exceeds the {$allowedDays} days allowed");
    }
}
