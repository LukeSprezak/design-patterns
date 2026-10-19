<?php

declare(strict_types=1);

namespace DesignPatterns\Enterprise\WebPresentation\Incident;

final class IncidentRegister
{
    /** @var array<int, Incident> */
    private array $incidents = [];

    public function report(string $address, int $requiredUnits): Incident
    {
        $incident = new Incident(count($this->incidents) + 1, $address, $requiredUnits);
        $this->incidents[$incident->id] = $incident;

        return $incident;
    }

    public function find(int $id): ?Incident
    {
        return $this->incidents[$id] ?? null;
    }

    /** @return list<Incident> */
    public function all(): array
    {
        return array_values($this->incidents);
    }
}
