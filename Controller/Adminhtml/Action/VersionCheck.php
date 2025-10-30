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
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;
use Mollie\Payment\Config;

class VersionCheck extends Action implements HttpPostActionInterface
{
    public function __construct(
        Context $context,
        private JsonFactory $resultJsonFactory,
        private JsonSerializer $json,
        private Config $config,
        private File $file,
    ) {
        parent::__construct($context);
    }

    public function execute(): Json
    {
        $resultJson = $this->resultJsonFactory->create();
        $result = $this->getVersions();
        $current = $latest = $this->config->getVersion();
        $changeLog = [];
        if ($result) {
            $data = $this->json->unserialize($result);
            $latest = $data[0]['tag_name'];
            foreach ($data as $release) {
                if ($release['tag_name'] == $this->config->getVersion()) {
                    break;
                }
                $changeLog[] = [
                    $release['tag_name'] => $release['body'],
                ];
            }
        }

        $data = [
            'current_verion' => $current,
            'last_version' => $latest,
            'changelog' => $changeLog,
        ];

        return $resultJson->setData(['result' => $data]);
    }

    private function getVersions(): string
    {
        try {
            // Github required a User-Agent
            $options = [
                'http' => [
                    'method' => 'GET',
                    'header' => [
                        'User-Agent: PHP',
                    ],
                ],
            ];

            return $this->file->fileGetContents(
                'https://api.github.com/repos/mollie/magento2/releases',
                null,
                // @phpcs:ignore Magento2.Functions.DiscouragedFunction.Discouraged
                stream_context_create($options),
            );
        } catch (Exception) {
            return '';
        }
    }
}
