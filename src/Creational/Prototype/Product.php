<?php

declare(strict_types=1);

namespace DesignPatterns\Creational\Prototype;

final readonly class Product
{
    /**
     * @param list<string> $categories
     * @param list<string> $imageUrls
     */
    public function __construct(
        public ?int $id,
        public string $sku,
        public string $name,
        public string $description,
        public string $material,
        public array $categories,
        public array $imageUrls,
        public int $priceInCents,
        public ?Size $size,
        public ?Color $color,
    ) {
    }

    public function variant(string $sku, Size $size, Color $color, ?int $priceInCents = null): self
    {
        return clone($this, [
            'sku' => $sku,
            'size' => $size,
            'color' => $color,
            'priceInCents' => $priceInCents ?? $this->priceInCents,
        ]);
    }

    // A clone is a new catalog record; keeping the prototype's id would overwrite it on persist.
    public function __clone(): void
    {
        $this->id = null;
    }
}
