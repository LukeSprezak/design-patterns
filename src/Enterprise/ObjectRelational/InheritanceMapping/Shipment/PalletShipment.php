<?php

declare(strict_types=1);

namespace DesignPatterns\Enterprise\ObjectRelational\InheritanceMapping\Shipment;

final readonly class PalletShipment extends Shipment
{
    private const int PRICE_PER_PALLET_IN_CENTS = 12000;
    private const int DEPOSIT_PER_UNRETURNED_PALLET_IN_CENTS = 4000;

    public function __construct(
        string $trackingNumber,
        string $senderName,
        public int $palletCount,
        public bool $exchangesEuroPallets,
    ) {
        parent::__construct($trackingNumber, $senderName);
    }

    #[\Override]
    public function priceInCents(): int
    {
        $pricePerPalletInCents = self::PRICE_PER_PALLET_IN_CENTS
            + ($this->exchangesEuroPallets ? 0 : self::DEPOSIT_PER_UNRETURNED_PALLET_IN_CENTS);

        return $this->palletCount * $pricePerPalletInCents;
    }
}
