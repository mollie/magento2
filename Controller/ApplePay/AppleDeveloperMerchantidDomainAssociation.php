<?php

/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Controller\ApplePay;

use Exception;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\Module\Dir;
use Mollie\Payment\Config;
use Mollie\Payment\Service\Mollie\ApplePay\Certificate;

class AppleDeveloperMerchantidDomainAssociation implements HttpGetActionInterface
{
    public function __construct(
        private Config $config,
        private ResultFactory $resultFactory,
        private File $driverFile,
        private Dir $moduleDir,
        private Certificate $certificate
    ) {}

    public function execute()
    {
        try {
            $contents = $this->certificate->execute();
        } catch (Exception $exception) {
            $this->config->addToLog('Unable to retrieve Apple Pay certificate', [$exception->getTraceAsString()]);
            $path = $this->moduleDir->getDir('Mollie_Payment');
            $contents = $this->driverFile->fileGetContents($path . '/apple-developer-merchantid-domain-association');
        }

        $response = $this->resultFactory->create(ResultFactory::TYPE_RAW);
        $response->setHeader('Content-Type', 'text/plain');
        $response->setContents($contents);

        return $response;
    }
}
