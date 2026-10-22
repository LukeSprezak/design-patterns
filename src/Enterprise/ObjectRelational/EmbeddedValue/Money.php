<?php

declare(strict_types=1);

namespace DesignPatterns\Enterprise\ObjectRelational\EmbeddedValue;

final readonly class Money
{
    public function __construct(
        public int $amountInCents,
        public Currency $currency,
    ) {
    }

    public function add(Money $other): self
    {
        if ($other->currency !== $this->currency) {
            throw CurrencyMismatchException::between($this, $other);
        }

        return new self($this->amountInCents + $other->amountInCents, $this->currency);
    }
}
