<?php

declare(strict_types=1);

namespace DesignPatterns\Tests\Enterprise\DataSource\DataMapper;

use DesignPatterns\Enterprise\DataSource\DataMapper\PdoTransactionManager;
use PDO;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;

#[CoversClass(PdoTransactionManager::class)]
final class PdoTransactionManagerTest extends TestCase
{
    private PDO $connection;
    private PdoTransactionManager $transactions;

    protected function setUp(): void
    {
        $this->connection = PharmacyFixture::connection();
        $this->transactions = new PdoTransactionManager($this->connection);
    }

    #[Test]
    public function commitsWhenOperationReturnsAndPassesItsResultThrough(): void
    {
        $result = $this->transactions->transactional(function (): string {
            $this->connection->exec("UPDATE medications SET stock = 0 WHERE ean = '5901234123457'");

            return 'done';
        });

        self::assertSame('done', $result);
        self::assertSame(0, $this->stockOfApap());
    }

    #[Test]
    public function rollsBackAndRethrowsWhenOperationThrows(): void
    {
        try {
            $this->transactions->transactional(function (): never {
                $this->connection->exec("UPDATE medications SET stock = 0 WHERE ean = '5901234123457'");
                throw new RuntimeException('printer offline');
            });
            self::fail('Expected RuntimeException');
        } catch (RuntimeException $runtimeException) {
            self::assertSame('printer offline', $runtimeException->getMessage());
        }

        self::assertSame(10, $this->stockOfApap());
        self::assertFalse($this->connection->inTransaction());
    }

    private function stockOfApap(): int
    {
        return (int) $this->connection->query("SELECT stock FROM medications WHERE ean = '5901234123457'")->fetchColumn();
    }
}
