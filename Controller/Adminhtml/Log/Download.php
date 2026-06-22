<?php

/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Controller\Adminhtml\Log;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\AuthorizationInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Message\ManagerInterface;
use Mollie\Payment\Logger\MollieLogger;
use Mollie\Payment\Service\Config\Debug\DebugBundleGenerator;

class Download implements HttpGetActionInterface
{
    public const ADMIN_RESOURCE = 'Mollie_Payment::config';

    public function __construct(
        private readonly DebugBundleGenerator $generator,
        private readonly FileFactory $fileFactory,
        private readonly MollieLogger $logger,
        private readonly AuthorizationInterface $authorization,
        private readonly ManagerInterface $messageManager,
        private readonly ResultFactory $resultFactory,
    ) {
    }

    public function execute()
    {
        if (!$this->authorization->isAllowed(self::ADMIN_RESOURCE)) {
            $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
            $resultRedirect->setPath('adminhtml');
            return $resultRedirect;
        }

        try {
            $relativePath = $this->generator->generate();
            $filename = $this->generator->buildFilename();

            return $this->fileFactory->create(
                $filename,
                [
                    'type' => 'filename',
                    'value' => $relativePath,
                    'rm' => true,
                ],
                DirectoryList::VAR_DIR,
                'application/gzip'
            );
        } catch (LocalizedException $exception) {
            $this->messageManager->addErrorMessage($exception->getMessage());
        } catch (\Throwable $exception) {
            $this->logger->addErrorLog('debug_bundle', $exception->getMessage());
            $this->messageManager->addErrorMessage(
                __('Unable to generate the debug bundle: %1', $exception->getMessage())
            );
        }

        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $resultRedirect->setPath('adminhtml/system_config/edit', ['section' => 'mollie_developer_settings']);

        return $resultRedirect;
    }
}
