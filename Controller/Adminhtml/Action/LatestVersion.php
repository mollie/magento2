<?php

/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Controller\Adminhtml\Action;

use Exception;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;

class LatestVersion extends Action implements HttpGetActionInterface
{
    public const ADMIN_RESOURCE = 'Mollie_Payment::config';

    public function __construct(
        Context $context,
        private readonly JsonFactory $resultJsonFactory,
        private readonly JsonSerializer $json,
        private readonly File $file,
    ) {
        parent::__construct($context);
    }

    public function execute(): Json
    {
        $resultJson = $this->resultJsonFactory->create();
        $package = $this->getRequest()->getParam('package');

        if (!$package || !preg_match('#^[a-z0-9-]+/[a-z0-9-]+$#', $package)) {
            return $resultJson->setData(['error' => 'Invalid package name']);
        }

        try {
            $url = 'https://repo.packagist.org/p2/' . $package . '.json';
            $response = $this->file->fileGetContents($url);
            $data = $this->json->unserialize($response);
        } catch (Exception) {
            return $resultJson->setData(['latest_version' => null]);
        }

        $packageName = array_key_first($data['packages'] ?? []);
        if (!$packageName) {
            return $resultJson->setData(['latest_version' => null]);
        }

        foreach ($data['packages'][$packageName] as $release) {
            $version = $release['version'] ?? '';

            if (str_starts_with($version, 'dev-')) {
                continue;
            }

            if (preg_match('/-(alpha|beta|rc|dev|patch)/i', $version)) {
                continue;
            }

            return $resultJson->setData([
                'latest_version' => $release['version_normalized'] ?? $version,
                'latest_version_tag' => $version,
            ]);
        }

        return $resultJson->setData(['latest_version' => null]);
    }
}
