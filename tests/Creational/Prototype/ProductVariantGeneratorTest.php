<?php

declare(strict_types=1);

namespace DesignPatterns\Tests\Creational\Prototype;

use DesignPatterns\Creational\Prototype\Color;
use DesignPatterns\Creational\Prototype\Product;
use DesignPatterns\Creational\Prototype\ProductVariantGenerator;
use DesignPatterns\Creational\Prototype\Size;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(ProductVariantGenerator::class)]
final class ProductVariantGeneratorTest extends TestCase
{
    #[Test]
    public function generatesOneVariantPerSizeAndColorCombination(): void
    {
        $prototype = new Product(
            id: 42,
            sku: 'TS-001',
            name: 'T-shirt Basic',
            description: 'Classic crew neck t-shirt with a relaxed fit.',
            material: '100% organic cotton',
            categories: ['men', 't-shirts'],
            imageUrls: ['https://cdn.example.com/ts-001-front.jpg'],
            priceInCents: 4900,
            size: null,
            color: null,
        );

        $variants = new ProductVariantGenerator()->generate(
            $prototype,
            sizes: [Size::S, Size::M, Size::L],
            colors: [Color::Black, Color::White],
        );

        self::assertCount(6, $variants);
        self::assertSame(
            ['TS-001-S-BLACK', 'TS-001-S-WHITE', 'TS-001-M-BLACK', 'TS-001-M-WHITE', 'TS-001-L-BLACK', 'TS-001-L-WHITE'],
            array_map(static fn(Product $variant): string => $variant->sku, $variants),
        );
        self::assertTrue(array_all($variants, static fn(Product $variant): bool => $variant->id === null));
        self::assertTrue(array_all($variants, static fn(Product $variant): bool => $variant->name === 'T-shirt Basic'));
    }
}
