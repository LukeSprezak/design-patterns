<?php

declare(strict_types=1);

namespace DesignPatterns\Structural\Facade\Ksef;

final readonly class KsefAuthenticationClient
{
    public function __construct(
        private KsefHttpClient $ksefHttpClient,
    ) {
    }

    public function authenticate(string $sellerTaxId, #[\SensitiveParameter] string $ksefToken): string
    {
        $challengeResponse = $this->ksefHttpClient->post('/auth/challenge', [], null);

        $authenticationResponse = $this->ksefHttpClient->post('/auth/ksef-token', [
            'challenge' => $challengeResponse['challenge'],
            'contextIdentifier' => ['type' => 'Nip', 'value' => $sellerTaxId],
            // KSeF expects "token|timestamp" encrypted with the Ministry of Finance RSA public key;
            // base64 stands in for it to keep key management out of the example.
            'encryptedToken' => base64_encode($ksefToken),
        ], null);

        $redeemResponse = $this->ksefHttpClient->post('/auth/token/redeem', [], $authenticationResponse['authenticationToken']['token']);

        return $redeemResponse['accessToken']['token'];
    }
}
