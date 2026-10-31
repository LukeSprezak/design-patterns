<?php

declare(strict_types=1);

namespace DesignPatterns\Structural\Facade\Ksef;

final readonly class KsefSessionClient
{
    public function __construct(
        private KsefHttpClient $ksefHttpClient,
    ) {
    }

    public function openOnlineSession(string $accessToken, EncryptionKey $encryptionKey): string
    {
        $sessionResponse = $this->ksefHttpClient->post('/sessions/online', [
            'formCode' => ['systemCode' => 'FA (3)', 'schemaVersion' => '1-0E', 'value' => 'FA'],
            'encryption' => [
                // KSeF expects the symmetric key encrypted with the Ministry of Finance RSA public key, see KsefAuthenticationClient.
                'encryptedSymmetricKey' => base64_encode($encryptionKey->key),
                'initializationVector' => base64_encode($encryptionKey->initializationVector),
            ],
        ], $accessToken);

        return $sessionResponse['referenceNumber'];
    }

    public function closeSession(string $accessToken, string $sessionReferenceNumber): void
    {
        $this->ksefHttpClient->post("/sessions/online/{$sessionReferenceNumber}/close", [], $accessToken);
    }
}
