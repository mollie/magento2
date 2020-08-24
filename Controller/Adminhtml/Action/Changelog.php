<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Mollie\Payment\Controller\Adminhtml\Action;

use Magento\Backend\App\Action;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;
use Mollie\Payment\Config;

class Changelog extends Action
{
    /**
     * @var JsonFactory
     */
    private $resultJsonFactory;

    /**
     * @var JsonSerializer
     */
    private $json;

    /**
     * @var File
     */
    private $file;

    public function __construct(
        Action\Context $context,
        JsonFactory $resultJsonFactory,
        JsonSerializer $json,
        File $file
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->json = $json;
        $this->file = $file;
        parent::__construct($context);
    }

    public function execute()
    {
        $resultJson = $this->resultJsonFactory->create();
        $result = $this->getVersions();
        $data = $this->json->unserialize($result);

        return $resultJson->setData($data);
    }

    /**
     * @return string
     * @throws FileSystemException
     */
    private function getVersions(): string
    {
        return $this->file->fileGetContents(
            sprintf(
                'http://version.magmodules.eu/%s.json',
                Config::EXTENSION_CODE
            )
        );
    }
}
