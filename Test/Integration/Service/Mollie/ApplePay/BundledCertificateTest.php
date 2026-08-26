<?php

/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Integration\Service\Mollie\ApplePay;

use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\HTTP\ClientInterface;
use Magento\Framework\Module\Dir;
use Mollie\Payment\Test\Integration\IntegrationTestCase;

class BundledCertificateTest extends IntegrationTestCase
{
    private const CERTIFICATE_URL = 'https://www.mollie.com/.well-known/apple-developer-merchantid-domain-association';
    private const BUNDLED_FILENAME = 'apple-developer-merchantid-domain-association';

    public function testBundledCertificateMatchesTheCertificateServedByMollie(): void
    {
        $bundledCertificate = $this->getBundledCertificate();

        $liveCertificate = $this->getLiveCertificate();

        $this->assertSame(
            $liveCertificate,
            $bundledCertificate,
            'The bundled Apple Pay domain validation file is outdated. ' .
            'Replace ' . self::BUNDLED_FILENAME . ' in the module root with the contents of ' . self::CERTIFICATE_URL
        );
    }

    private function getBundledCertificate(): string
    {
        $path = $this->objectManager->get(Dir::class)->getDir('Mollie_Payment') . '/' . self::BUNDLED_FILENAME;

        return $this->objectManager->get(File::class)->fileGetContents($path);
    }

    private function getLiveCertificate(): string
    {
        $client = $this->objectManager->create(ClientInterface::class);
        $client->get(self::CERTIFICATE_URL);

        $this->assertSame(200, $client->getStatus(), 'Unable to download the Apple Pay domain validation file from Mollie');

        return $client->getBody();
    }
}
