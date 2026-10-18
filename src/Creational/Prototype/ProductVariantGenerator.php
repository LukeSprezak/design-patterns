<?php

declare(strict_types=1);

namespace DesignPatterns\Creational\Prototype;

final class ProductVariantGenerator
{
    /**
     * @param list<Size> $sizes
     * @param list<Color> $colors
     * @return list<Product>
     */
    public function generate(Product $prototype, array $sizes, array $colors): array
    {
        $variants = [];
        foreach ($sizes as $size) {
            foreach ($colors as $color) {
                $variants[] = $prototype->variant(
                    sku: "{$prototype->sku}-{$size->value}-{$color->value}",
                    size: $size,
                    color: $color,
                );
            }
        }

        return $variants;
    }
}
