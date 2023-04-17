<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Mollie\Payment\Controller\Adminhtml\Action;

use Magento\Backend\App\Action;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;
use Mollie\Payment\Config;

class VersionCheck extends Action
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

    /**
     * @var Config
     */
    private $config;

    public function __construct(
        Action\Context $context,
        JsonFactory $resultJsonFactory,
        JsonSerializer $json,
        Config $config,
        File $file
    ) {
        parent::__construct($context);

        $this->resultJsonFactory = $resultJsonFactory;
        $this->json = $json;
        $this->file = $file;
        $this->config = $config;
    }

    public function execute()
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
                    $release['tag_name'] => $release['body']
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

    private function getVersions()
    {
        try {
            // Github required a User-Agent
            $options = [
                'http' => [
                    'method' => 'GET',
                    'header' => [
                        'User-Agent: PHP'
                    ]
                ]
            ];

            return $this->file->fileGetContents(
                'https://api.github.com/repos/mollie/magento2/releases',
                null,
                stream_context_create($options)
            );
        } catch (\Exception $exception) {
            return '';
        }
    }
}
