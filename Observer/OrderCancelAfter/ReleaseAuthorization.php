<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Observer\OrderCancelAfter;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Mollie\Payment\Service\Mollie\MollieApiClient;
use Mollie\Payment\Service\Mollie\Order\CanUseManualCapture;

class ReleaseAuthorization implements ObserverInterface
{
    public function __construct(
        private MollieApiClient $mollieApiClient,
        private CanUseManualCapture $canUseManualCapture
    ) {}

    public function execute(Observer $observer)
    {
        /** @var OrderInterface $order */
        $order = $observer->getEvent()->getOrder();

        if (!$this->canUseManualCapture->execute($order)) {
            return;
        }

        // If the order is only partially invoiced, we need to release the authorization.
        if ($order->getBaseTotalInvoiced() == 0 ||
            $order->getBaseTotalInvoiced() >= $order->getBaseGrandTotal()
        ) {
            return;
        }

        $mollieTransactionId = $order->getMollieTransactionId();
        $mollieApi = $this->mollieApiClient->loadByStore(storeId($order->getStoreId()));

        $mollieApi->payments->releaseAuthorization($mollieTransactionId);
    }
}
