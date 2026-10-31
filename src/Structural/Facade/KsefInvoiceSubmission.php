<?php

declare(strict_types=1);

namespace DesignPatterns\Structural\Facade;

use DesignPatterns\Structural\Facade\Ksef\InvoiceEncryptor;
use DesignPatterns\Structural\Facade\Ksef\KsefAuthenticationClient;
use DesignPatterns\Structural\Facade\Ksef\KsefInvoiceClient;
use DesignPatterns\Structural\Facade\Ksef\KsefSessionClient;

final readonly class KsefInvoiceSubmission
{
    public function __construct(
        private KsefAuthenticationClient $authenticationClient,
        private InvoiceEncryptor $invoiceEncryptor,
        private KsefSessionClient $sessionClient,
        private KsefInvoiceClient $invoiceClient,
        private string $sellerTaxId,
        #[\SensitiveParameter] private string $ksefToken,
    ) {
    }

    #[\NoDiscard('the KSeF number has to be stored with the invoice')]
    public function submit(string $invoiceXml): string
    {
        $accessToken = $this->authenticationClient->authenticate($this->sellerTaxId, $this->ksefToken);
        $encryptionKey = $this->invoiceEncryptor->generateEncryptionKey();
        $sessionReferenceNumber = $this->sessionClient->openOnlineSession($accessToken, $encryptionKey);

        $invoiceReferenceNumber = $this->invoiceClient->sendInvoice(
            $accessToken,
            $sessionReferenceNumber,
            $this->invoiceEncryptor->encrypt($invoiceXml, $encryptionKey),
        );
        $ksefNumber = $this->invoiceClient->ksefNumberOf($accessToken, $sessionReferenceNumber, $invoiceReferenceNumber);

        $this->sessionClient->closeSession($accessToken, $sessionReferenceNumber);

        return $ksefNumber;
    }
}
