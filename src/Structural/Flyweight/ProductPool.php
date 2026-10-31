<?php

declare(strict_types=1);

namespace DesignPatterns\Structural\Flyweight;

final class ProductPool
{
    /** @var array<string, Product> */
    private array $productsByEan = [];

    /** @param \Closure(): Product $createProduct called only for an EAN seen for the first time */
    public function productFor(string $ean, \Closure $createProduct): Product
    {
        return $this->productsByEan[$ean] ??= $createProduct();
    }
}
