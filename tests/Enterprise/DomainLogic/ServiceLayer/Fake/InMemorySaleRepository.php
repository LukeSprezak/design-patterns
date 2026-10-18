<?php

declare(strict_types=1);

namespace DesignPatterns\Tests\Enterprise\DomainLogic\ServiceLayer\Fake;

use DesignPatterns\Enterprise\DomainLogic\DomainModel\Sale\Sale;
use DesignPatterns\Enterprise\DomainLogic\ServiceLayer\SaleRepository;

final class InMemorySaleRepository implements SaleRepository
{
    /** @var list<Sale> */
    public private(set) array $sales = [];

    #[\Override]
    public function add(Sale $sale): void
    {
        $this->sales[] = $sale;
    }
}
