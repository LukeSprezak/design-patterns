<?php

declare(strict_types=1);

namespace DesignPatterns\Tests\Enterprise\DomainLogic\ServiceLayer\Fake;

use Closure;
use DesignPatterns\Enterprise\DomainLogic\ServiceLayer\TransactionManager;
use Throwable;

final class SpyTransactionManager implements TransactionManager
{
    public private(set) int $commits = 0;
    public private(set) int $rollbacks = 0;

    #[\Override]
    public function transactional(Closure $operation): mixed
    {
        try {
            $result = $operation();
            $this->commits++;

            return $result;
        } catch (Throwable $failure) {
            $this->rollbacks++;
            throw $failure;
        }
    }
}
