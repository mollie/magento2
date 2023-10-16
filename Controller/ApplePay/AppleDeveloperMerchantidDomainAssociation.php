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

class AppleDeveloperMerchantidDomainAssociation implements HttpGetActionInterface
{
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

    public function __construct(
        ResultFactory $resultFactory,
        File $driverFile,
        Dir $moduleDir
    ) {
        $this->resultFactory = $resultFactory;
        $this->driverFile = $driverFile;
        $this->moduleDir = $moduleDir;
    }

    public function execute()
    {
        $path = $this->moduleDir->getDir('Mollie_Payment');
        $contents =  $this->driverFile->fileGetContents($path . '/apple-developer-merchantid-domain-association');

        $response = $this->resultFactory->create(ResultFactory::TYPE_RAW);
        $response->setHeader('Content-Type', 'text/plain');
        $response->setContents($contents);

        return $response;
    }
}
