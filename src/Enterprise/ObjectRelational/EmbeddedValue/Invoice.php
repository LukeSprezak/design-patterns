<?php

declare(strict_types=1);

namespace DesignPatterns\Enterprise\ObjectRelational\EmbeddedValue;

/** A courier invoice: two value objects (Address, Money) live inside it, not in tables of their own. */
final class Invoice
{
    public function __construct(
        public readonly string $number,
        public readonly Address $recipient,
        public readonly Money $netAmount,
        public readonly Money $vatAmount,
    ) {
    }

    public Money $grossAmount {
        get => $this->netAmount->add($this->vatAmount);
    }
}
