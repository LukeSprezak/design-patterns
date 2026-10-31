<?php

declare(strict_types=1);

namespace DesignPatterns\Structural\Facade\Ksef;

final readonly class EncryptionKey
{
    public function __construct(
        #[\SensitiveParameter] public string $key,
        public string $initializationVector,
    ) {
    }
}
