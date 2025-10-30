<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Observer\MollieProcessTransactionEnd;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order;
use Mollie\Payment\Config;
use Mollie\Payment\Service\Order\DeletePaymentReminder;

class RemoveCompletedOrdersFromPendingPaymentTable implements ObserverInterface
{
    public function __construct(
        private Config $config,
        private DeletePaymentReminder $deletePaymentReminder
    ) {}

    public function execute(Observer $observer): void
    {
        /** @var OrderInterface $order */
        $order = $observer->getData('order');

        if (!$this->config->automaticallySendSecondChanceEmails(storeId($order->getStoreId()))) {
            return;
        }

        if (!in_array($order->getState(), [Order::STATE_PROCESSING, Order::STATE_COMPLETE])) {
            return;
        }

        $this->deletePaymentReminder->delete($order->getCustomerId() ?: $order->getCustomerEmail());
    }
}
