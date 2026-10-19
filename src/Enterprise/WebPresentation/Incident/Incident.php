<?php

declare(strict_types=1);

namespace DesignPatterns\Enterprise\WebPresentation\Incident;

final class Incident
{
    public private(set) IncidentState $state = IncidentState::Reported;
    public private(set) int $dispatchedUnits = 0;

    public bool $needsBackup {
        get => $this->dispatchedUnits < $this->requiredUnits;
    }

    public function __construct(
        public readonly int $id,
        public readonly string $address,
        public readonly int $requiredUnits,
    ) {
    }

    public function dispatch(int $units): void
    {
        $this->dispatchedUnits = $units;
        $this->state = IncidentState::Dispatched;
    }

    public function arrive(): void
    {
        $this->state = IncidentState::OnScene;
    }

    public function requestBackup(int $units): void
    {
        $this->dispatchedUnits += $units;
    }

    public function close(): void
    {
        $this->state = IncidentState::Closed;
    }
}
