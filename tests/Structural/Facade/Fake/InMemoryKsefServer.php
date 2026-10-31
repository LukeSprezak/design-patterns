<?php

declare(strict_types=1);

namespace DesignPatterns\Tests\Structural\Facade\Fake;

use DesignPatterns\Structural\Facade\Ksef\KsefHttpClient;

/**
 * Plays the KSeF side of the protocol and refuses every step taken out of order,
 * so a test passes only when the client follows the real sequence.
 */
final class InMemoryKsefServer implements KsefHttpClient
{
    /** @var list<string> */
    public private(set) array $decryptedInvoiceXmls = [];

    /** @var array<string, true> */
    private array $issuedChallenges = [];

    /** @var array<string, true> */
    private array $issuedAuthenticationTokens = [];

    /** @var array<string, true> */
    private array $issuedAccessTokens = [];

    /** @var array<string, array{key: string, initializationVector: string}> */
    private array $openSessionKeysByReferenceNumber = [];

    /** @var array<string, string> */
    private array $ksefNumbersByInvoiceReferenceNumber = [];

    public function __construct(
        private readonly string $sellerTaxId,
        #[\SensitiveParameter] private readonly string $ksefToken,
    ) {
    }

    public function numberOfOpenSessions(): int
    {
        return count($this->openSessionKeysByReferenceNumber);
    }

    #[\Override]
    public function post(string $path, array $body, ?string $bearerToken): array
    {
        return match (true) {
            $path === '/auth/challenge' => $this->issueChallenge(),
            $path === '/auth/ksef-token' => $this->authenticateWithKsefToken($body),
            $path === '/auth/token/redeem' => $this->redeemAccessToken($bearerToken),
            $path === '/sessions/online' => $this->openOnlineSession($body, $bearerToken),
            preg_match('#^/sessions/online/(\w+)/invoices$#', $path, $pathParameters) === 1 => $this->receiveInvoice($pathParameters[1], $body, $bearerToken),
            preg_match('#^/sessions/online/(\w+)/close$#', $path, $pathParameters) === 1 => $this->closeSession($pathParameters[1], $bearerToken),
        };
    }

    #[\Override]
    public function get(string $path, string $bearerToken): array
    {
        preg_match('#^/sessions/(\w+)/invoices/(\w+)$#', $path, $pathParameters);
        $this->requireAccessToken($bearerToken);
        $this->requireOpenSession($pathParameters[1]);

        return ['ksefNumber' => $this->ksefNumbersByInvoiceReferenceNumber[$pathParameters[2]]];
    }

    /** @return array{challenge: string} */
    private function issueChallenge(): array
    {
        $challenge = bin2hex(random_bytes(16));
        $this->issuedChallenges[$challenge] = true;

        return ['challenge' => $challenge];
    }

    /**
     * @param array<string, mixed> $body
     * @return array{authenticationToken: array{token: string}}
     */
    private function authenticateWithKsefToken(array $body): array
    {
        if (!isset($this->issuedChallenges[$body['challenge']])
            || $body['contextIdentifier'] !== ['type' => 'Nip', 'value' => $this->sellerTaxId]
            || base64_decode($body['encryptedToken']) !== $this->ksefToken
        ) {
            throw new \LogicException('Authentication rejected');
        }

        $authenticationToken = bin2hex(random_bytes(16));
        $this->issuedAuthenticationTokens[$authenticationToken] = true;

        return ['authenticationToken' => ['token' => $authenticationToken]];
    }

    /** @return array{accessToken: array{token: string}} */
    private function redeemAccessToken(?string $authenticationToken): array
    {
        if (!isset($this->issuedAuthenticationTokens[$authenticationToken])) {
            throw new \LogicException('Unknown authentication token');
        }

        $accessToken = bin2hex(random_bytes(16));
        $this->issuedAccessTokens[$accessToken] = true;

        return ['accessToken' => ['token' => $accessToken]];
    }

    /**
     * @param array<string, mixed> $body
     * @return array{referenceNumber: string}
     */
    private function openOnlineSession(array $body, ?string $accessToken): array
    {
        $this->requireAccessToken($accessToken);

        $sessionReferenceNumber = bin2hex(random_bytes(8));
        $this->openSessionKeysByReferenceNumber[$sessionReferenceNumber] = [
            'key' => base64_decode($body['encryption']['encryptedSymmetricKey']),
            'initializationVector' => base64_decode($body['encryption']['initializationVector']),
        ];

        return ['referenceNumber' => $sessionReferenceNumber];
    }

    /**
     * @param array<string, mixed> $body
     * @return array{referenceNumber: string}
     */
    private function receiveInvoice(string $sessionReferenceNumber, array $body, ?string $accessToken): array
    {
        $this->requireAccessToken($accessToken);
        $sessionKey = $this->requireOpenSession($sessionReferenceNumber);

        $encryptedInvoiceContent = base64_decode($body['encryptedInvoiceContent']);
        $invoiceXml = openssl_decrypt($encryptedInvoiceContent, 'aes-256-cbc', $sessionKey['key'], OPENSSL_RAW_DATA, $sessionKey['initializationVector']);

        if ($invoiceXml === false
            || $body['encryptedInvoiceHash'] !== base64_encode(hash('sha256', $encryptedInvoiceContent, true))
            || $body['encryptedInvoiceSize'] !== strlen($encryptedInvoiceContent)
            || $body['invoiceHash'] !== base64_encode(hash('sha256', $invoiceXml, true))
            || $body['invoiceSize'] !== strlen($invoiceXml)
        ) {
            throw new \LogicException('Invoice does not match its hashes or session key');
        }

        $this->decryptedInvoiceXmls[] = $invoiceXml;
        $invoiceReferenceNumber = bin2hex(random_bytes(8));
        $this->ksefNumbersByInvoiceReferenceNumber[$invoiceReferenceNumber] = sprintf(
            '%s-%s-%s-%s',
            $this->sellerTaxId,
            date('Ymd'),
            strtoupper(bin2hex(random_bytes(6))),
            strtoupper(bin2hex(random_bytes(1))),
        );

        return ['referenceNumber' => $invoiceReferenceNumber];
    }

    /** @return array{} */
    private function closeSession(string $sessionReferenceNumber, ?string $accessToken): array
    {
        $this->requireAccessToken($accessToken);
        $this->requireOpenSession($sessionReferenceNumber);

        unset($this->openSessionKeysByReferenceNumber[$sessionReferenceNumber]);

        return [];
    }

    private function requireAccessToken(?string $accessToken): void
    {
        if (!isset($this->issuedAccessTokens[$accessToken])) {
            throw new \LogicException('Unknown access token');
        }
    }

    /** @return array{key: string, initializationVector: string} */
    private function requireOpenSession(string $sessionReferenceNumber): array
    {
        return $this->openSessionKeysByReferenceNumber[$sessionReferenceNumber]
            ?? throw new \LogicException('Session is not open');
    }
}
