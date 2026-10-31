<?php

declare(strict_types=1);

namespace DesignPatterns\Structural\Facade\Ksef;

final readonly class InvoiceEncryptor
{
    public function generateEncryptionKey(): EncryptionKey
    {
        return new EncryptionKey(key: random_bytes(32), initializationVector: random_bytes(16));
    }

    public function encrypt(string $invoiceXml, EncryptionKey $encryptionKey): EncryptedInvoice
    {
        $encryptedInvoiceContent = openssl_encrypt($invoiceXml, 'aes-256-cbc', $encryptionKey->key, OPENSSL_RAW_DATA, $encryptionKey->initializationVector);

        return new EncryptedInvoice(
            invoiceHash: base64_encode(hash('sha256', $invoiceXml, true)),
            invoiceSize: strlen($invoiceXml),
            encryptedInvoiceHash: base64_encode(hash('sha256', $encryptedInvoiceContent, true)),
            encryptedInvoiceSize: strlen($encryptedInvoiceContent),
            encryptedInvoiceContent: $encryptedInvoiceContent,
        );
    }
}
