<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Controller\ApplePay;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\Module\Dir;
use Mollie\Payment\Config;
use Mollie\Payment\Service\Mollie\ApplePay\Certificate;

class AppleDeveloperMerchantidDomainAssociation implements HttpGetActionInterface
{
    /**
     * @var Config
     */
    private $config;
    /**
     * @var ResultFactory
     */
    private $resultFactory;
    /**
     * @var File
     */
    private $driverFile;
    /**
     * @var Dir
     */
    private $moduleDir;
    /**
     * @var Certificate
     */
    private $certificate;

    public function __construct(
        Config $config,
        ResultFactory $resultFactory,
        File $driverFile,
        Dir $moduleDir,
        Certificate $certificate
    ) {
        $this->resultFactory = $resultFactory;
        $this->driverFile = $driverFile;
        $this->moduleDir = $moduleDir;
        $this->certificate = $certificate;
        $this->config = $config;
    }

    public function execute()
    {
        try {
            $contents = $this->certificate->execute();
        } catch (\Exception $exception) {
            $this->config->addToLog('Unable to retrieve Apple Pay certificate', [$exception->getTraceAsString()]);
            $path = $this->moduleDir->getDir('Mollie_Payment');
            $contents =  $this->driverFile->fileGetContents($path . '/apple-developer-merchantid-domain-association');
        }

        $response = $this->resultFactory->create(ResultFactory::TYPE_RAW);
        $response->setHeader('Content-Type', 'text/plain');
        $response->setContents($contents);

        return $response;
    }
}
