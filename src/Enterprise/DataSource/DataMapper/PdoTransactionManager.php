<?php

declare(strict_types=1);

namespace DesignPatterns\Enterprise\DataSource\DataMapper;

use Closure;
use DesignPatterns\Enterprise\DomainLogic\ServiceLayer\TransactionManager;
use PDO;
use Throwable;

final readonly class PdoTransactionManager implements TransactionManager
{
    public function __construct(private PDO $connection)
    {
    }

    #[\Override]
    public function transactional(Closure $operation): mixed
    {
        $this->connection->beginTransaction();
        try {
            $result = $operation();
        } catch (Throwable $failure) {
            $this->connection->rollBack();
            throw $failure;
        }
        $this->connection->commit();

        return $result;
    }
}
