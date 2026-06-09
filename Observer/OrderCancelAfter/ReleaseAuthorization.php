<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Observer\OrderCancelAfter;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Message\ManagerInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Mollie\Api\Exceptions\ApiException;
use Mollie\Payment\Logger\MollieLogger;
use Mollie\Payment\Service\Mollie\MollieApiClient;
use Mollie\Payment\Service\Mollie\Order\CanUseManualCapture;

class ReleaseAuthorization implements ObserverInterface
{
    public function __construct(
        private MollieApiClient $mollieApiClient,
        private CanUseManualCapture $canUseManualCapture,
        private MollieLogger $logger,
        private ManagerInterface $messageManager
    ) {}

    public function execute(Observer $observer)
    {
        /** @var OrderInterface $order */
        $order = $observer->getEvent()->getOrder();

        if (!$this->canUseManualCapture->execute($order)) {
            return;
        }

        if ($order->getBaseTotalInvoiced() >= $order->getBaseGrandTotal()) {
            return;
        }

        $mollieTransactionId = $order->getMollieTransactionId();
        if ($mollieTransactionId === null) {
            return;
        }

        $mollieApi = $this->mollieApiClient->loadByStore(storeId($order->getStoreId()));

        try {
            $mollieApi->payments->releaseAuthorization($mollieTransactionId);
        } catch (ApiException $exception) {
            $this->logger->addErrorLog(
                'release_authorization',
                'Could not release authorization for ' . $mollieTransactionId . ': ' . $exception->getMessage()
            );

            $this->messageManager->addWarningMessage(
                __(
                    'The order was canceled, but the remaining Mollie payment authorization (%1) could not be ' .
                    'released automatically: %2. Any uncaptured amount is released by Mollie when the authorization ' .
                    'expires.',
                    $mollieTransactionId,
                    $exception->getMessage()
                )
            );
        }
    }
}
