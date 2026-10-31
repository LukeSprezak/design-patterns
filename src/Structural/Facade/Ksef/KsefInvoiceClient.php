<?php

declare(strict_types=1);

namespace DesignPatterns\Structural\Facade\Ksef;

final readonly class KsefInvoiceClient
{
    public function __construct(
        private KsefHttpClient $ksefHttpClient,
    ) {
    }

    public function sendInvoice(string $accessToken, string $sessionReferenceNumber, EncryptedInvoice $encryptedInvoice): string
    {
        $invoiceResponse = $this->ksefHttpClient->post("/sessions/online/{$sessionReferenceNumber}/invoices", [
            'invoiceHash' => $encryptedInvoice->invoiceHash,
            'invoiceSize' => $encryptedInvoice->invoiceSize,
            'encryptedInvoiceHash' => $encryptedInvoice->encryptedInvoiceHash,
            'encryptedInvoiceSize' => $encryptedInvoice->encryptedInvoiceSize,
            'encryptedInvoiceContent' => base64_encode($encryptedInvoice->encryptedInvoiceContent),
        ], $accessToken);

        return $invoiceResponse['referenceNumber'];
    }

    public function ksefNumberOf(string $accessToken, string $sessionReferenceNumber, string $invoiceReferenceNumber): string
    {
        return $this->ksefHttpClient->get("/sessions/{$sessionReferenceNumber}/invoices/{$invoiceReferenceNumber}", $accessToken)['ksefNumber'];
    }
}
