<?php

declare(strict_types=1);

namespace DesignPatterns\Creational\Singleton;

use LogicException;

final class FiscalPrinterConnection
{
    private static ?self $instance = null;

    private int $lastReceiptNumber = 0;

    /** @var array<int, list<string>> receipt number => printed lines */
    private array $journal = [];

    // The printer hangs on a single serial port; opening it twice in one process corrupts receipt numbering.
    private function __construct()
    {
    }

    public static function getInstance(): self
    {
        return self::$instance ??= new self();
    }

    /** @param list<string> $lines */
    #[\NoDiscard('the receipt number must be stored with the sale')]
    public function printReceipt(array $lines): int
    {
        $receiptNumber = ++$this->lastReceiptNumber;
        $this->journal[$receiptNumber] = $lines;

        return $receiptNumber;
    }

    /** @return array<int, list<string>> */
    public function journal(): array
    {
        return $this->journal;
    }

    private function __clone()
    {
    }

    /** @param array<string, mixed> $data */
    public function __unserialize(array $data): void
    {
        throw new LogicException('FiscalPrinterConnection cannot be restored from serialized form; use getInstance()');
    }
}
