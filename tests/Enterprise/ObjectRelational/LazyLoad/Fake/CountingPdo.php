<?php

declare(strict_types=1);

namespace DesignPatterns\Tests\Enterprise\ObjectRelational\LazyLoad\Fake;

use PDO;
use PDOStatement;

/** A PDO that counts prepared statements, so a test can prove when the database was hit. */
final class CountingPdo extends PDO
{
    public private(set) int $preparedStatements = 0;

    #[\Override]
    public function prepare(string $query, array $options = []): PDOStatement|false
    {
        $this->preparedStatements++;

        return parent::prepare($query, $options);
    }
}
