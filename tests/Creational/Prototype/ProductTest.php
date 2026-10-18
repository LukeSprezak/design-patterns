<?php

declare(strict_types=1);

namespace DesignPatterns\Tests\Creational\Prototype;

use DesignPatterns\Creational\Prototype\Color;
use DesignPatterns\Creational\Prototype\Product;
use DesignPatterns\Creational\Prototype\Size;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(Product::class)]
final class ProductTest extends TestCase
{
    #[Test]
    public function variantInheritsCatalogDataFromPrototype(): void
    {
        $variant = self::baseProduct()->variant(sku: 'TS-001-M-BLACK', size: Size::M, color: Color::Black);

        self::assertSame('TS-001-M-BLACK', $variant->sku);
        self::assertSame(Size::M, $variant->size);
        self::assertSame(Color::Black, $variant->color);
        self::assertSame('T-shirt Basic', $variant->name);
        self::assertSame('100% organic cotton', $variant->material);
        self::assertSame(['men', 't-shirts'], $variant->categories);
        self::assertSame(['https://cdn.example.com/ts-001-front.jpg'], $variant->imageUrls);
        self::assertSame(4900, $variant->priceInCents);
    }

    #[Test]
    public function variantHasNoIdSoItIsPersistedAsNewRecord(): void
    {
        $variant = self::baseProduct()->variant(sku: 'TS-001-M-BLACK', size: Size::M, color: Color::Black);

        self::assertNull($variant->id);
    }

    #[Test]
    public function variantCanOverridePrice(): void
    {
        $variant = self::baseProduct()->variant(
            sku: 'TS-001-XL-BLACK',
            size: Size::XL,
            color: Color::Black,
            priceInCents: 5400,
        );

        self::assertSame(5400, $variant->priceInCents);
    }

    #[Test]
    public function prototypeStaysUnchangedAfterCreatingVariants(): void
    {
        $prototype = self::baseProduct();

        $prototype->variant(sku: 'TS-001-M-BLACK', size: Size::M, color: Color::Black, priceInCents: 5400);

        self::assertSame(42, $prototype->id);
        self::assertSame('TS-001', $prototype->sku);
        self::assertNull($prototype->size);
        self::assertNull($prototype->color);
        self::assertSame(4900, $prototype->priceInCents);
    }

    private static function baseProduct(): Product
    {
        return new Product(
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
    }
}
