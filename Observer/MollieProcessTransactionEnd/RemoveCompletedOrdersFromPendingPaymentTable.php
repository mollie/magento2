<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 *  See COPYING.txt for license details.
 */

namespace Mollie\Payment\Observer\MollieProcessTransactionEnd;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order;
use Mollie\Payment\Config;
use Mollie\Payment\Service\Order\DeletePaymentReminder;

class RemoveCompletedOrdersFromPendingPaymentTable implements ObserverInterface
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var DeletePaymentReminder
     */
    private $deletePaymentReminder;

    public function __construct(
        Config $config,
        DeletePaymentReminder $deletePaymentReminder
    ) {
        $this->config = $config;
        $this->deletePaymentReminder = $deletePaymentReminder;
    }

    public function execute(Observer $observer)
    {
        /** @var OrderInterface $order */
        $order = $observer->getData('order');

        if (!$this->config->automaticallySendSecondChanceEmails($order->getStoreId())) {
            return;
        }

        if (!in_array($order->getState(), [Order::STATE_PROCESSING, Order::STATE_COMPLETE])) {
            return;
        }

        $this->deletePaymentReminder->delete($order->getCustomerId() ?: $order->getCustomerEmail());
    }
}
