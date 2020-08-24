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
use Magmodules\Dummy\Api\Config\RepositoryInterface as ConfigRepository;
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
            $versions = array_keys($data);
            $latest = reset($versions);
            foreach ($data as $version => $changes) {
                if ('v' . $version == $this->config->getVersion()) {
                    break;
                }
                $changeLog[] = [
                    $version => $changes['changelog']
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
            return $this->file->fileGetContents(
                sprintf(
                    'http://version.magmodules.eu/%s.json',
                    ConfigRepository::EXTENSION_CODE
                )
            );
        } catch (\Exception $exception) {
            return '';
        }
    }
}
