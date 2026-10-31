<?php

declare(strict_types=1);

namespace DesignPatterns\Structural\Facade\Ksef;

final readonly class EncryptedInvoice
{
    public function __construct(
        public string $invoiceHash,
        public int $invoiceSize,
        public string $encryptedInvoiceHash,
        public int $encryptedInvoiceSize,
        public string $encryptedInvoiceContent,
    ) {
    }
}
