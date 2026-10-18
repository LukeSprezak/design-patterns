<?php

declare(strict_types=1);

namespace DesignPatterns\Creational\Builder\Medication;

enum MedicationCategory
{
    case Standard;
    case Antibiotic;
    case Controlled;
}
