<?php

declare(strict_types=1);

namespace DesignPatterns\Enterprise\DomainLogic\ServiceLayer;

use Closure;

interface TransactionManager
{
    /**
     * Runs the operation inside one transaction: commits when it returns, rolls back when it throws.
     *
     * @template TResult
     * @param Closure(): TResult $operation
     * @return TResult
     */
    public function transactional(Closure $operation): mixed;
}
