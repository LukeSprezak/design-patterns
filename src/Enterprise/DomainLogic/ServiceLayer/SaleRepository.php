<?php

declare(strict_types=1);

namespace DesignPatterns\Enterprise\DomainLogic\ServiceLayer;

use DesignPatterns\Enterprise\DomainLogic\DomainModel\Sale\Sale;

interface SaleRepository
{
    public function add(Sale $sale): void;
}
