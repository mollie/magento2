<?php

/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Controller\Adminhtml\Log;

use Exception;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Filesystem\DirectoryList;
use Magento\Framework\Filesystem\Driver\File;

class Debug extends Action implements HttpPostActionInterface
{
    /**
     * Debug log file path pattern
     */
    public const DEBUG_LOG_FILE = '%s/log/mollie.log';

    /**
     * Limit stream size to 100 lines
     */
    public const MAX_LINES = 100;

    public function __construct(
        Context $context,
        private JsonFactory $resultJsonFactory,
        private DirectoryList $dir,
        private File $file,
    ) {
        parent::__construct($context);
    }

    public function execute(): Json
    {
        $resultJson = $this->resultJsonFactory->create();
        if ($this->isLogExists(self::DEBUG_LOG_FILE)) {
            $result = ['result' => $this->prepareLogText(self::DEBUG_LOG_FILE)];
        } else {
            $result = __('Log is empty');
        }

        return $resultJson->setData($result);
    }

    private function isLogExists(string $file): bool
    {
        try {
            $logFile = sprintf($file, $this->dir->getPath('var'));

            return $this->file->isExists($logFile);
        } catch (Exception $e) {
            return false;
        }
    }

    private function prepareLogText(string $file): array
    {
        $logFile = sprintf($file, $this->dir->getPath('var'));
        $file = $this->file->fileOpen($logFile, 'r');
        $count = 0;

        $result = [];
        // phpcs:ignore Magento2.Functions.DiscouragedFunction
        while (($line = fgets($file)) !== false && $count < self::MAX_LINES) {
            $data = explode('] ', $line);
            $date = ltrim(array_shift($data), '[');
            $data = implode('] ', $data);
            $data = explode(': ', $data);
            array_shift($data);
            $result[] = [
                'date' => $date,
                'msg' => implode(': ', $data),
            ];
            $count++;
        }

        $this->file->fileClose($file);

        return $result;
    }
}
