<?php

declare(strict_types=1);

namespace DesignPatterns\Enterprise\ObjectRelational\IdentityMap;

final class IdentityMap
{
    /** @var array<class-string, array<string|int, object>> */
    private array $objectsByClassAndId = [];

    /**
     * @template TObject of object
     * @param class-string<TObject> $class
     * @return TObject|null
     */
    public function get(string $class, string|int $id): ?object
    {
        return $this->objectsByClassAndId[$class][$id] ?? null;
    }

    public function add(string|int $id, object $object): void
    {
        $this->objectsByClassAndId[$object::class][$id] = $object;
    }
}
