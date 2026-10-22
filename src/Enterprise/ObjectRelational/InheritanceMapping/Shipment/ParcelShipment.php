<?php

declare(strict_types=1);

namespace DesignPatterns\Enterprise\ObjectRelational\InheritanceMapping\Shipment;

final readonly class ParcelShipment extends Shipment
{
    private const int BASE_PRICE_IN_CENTS = 1500;
    private const int PRICE_PER_STARTED_KILOGRAM_IN_CENTS = 200;

    public function __construct(
        string $trackingNumber,
        string $senderName,
        public int $weightInGrams,
    ) {
        parent::__construct($trackingNumber, $senderName);
    }

    #[\Override]
    public function priceInCents(): int
    {
        $startedKilograms = intdiv($this->weightInGrams + 999, 1000);

        return self::BASE_PRICE_IN_CENTS + $startedKilograms * self::PRICE_PER_STARTED_KILOGRAM_IN_CENTS;
    }
}
