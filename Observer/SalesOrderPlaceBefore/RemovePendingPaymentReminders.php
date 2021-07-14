<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 *  See COPYING.txt for license details.
 */

namespace Mollie\Payment\Observer\SalesOrderPlaceBefore;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order;
use Mollie\Payment\Config;
use Mollie\Payment\Service\Order\DeletePaymentReminder;

class RemovePendingPaymentReminders implements ObserverInterface
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

    /**
     * Remove any pending payment reminders. This is to prevent that payment reminders get send if the order was paid
     * by a payment method that is not from Mollie.
     *
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        /** @var OrderInterface $order */
        $order = $observer->getData('order');

        $email = $order->getCustomerEmail();
        if (!$this->config->automaticallySendSecondChanceEmails($order->getStoreId()) || !$email) {
            return;
        }

        $this->deletePaymentReminder->delete($order->getCustomerId() ?: $order->getCustomerEmail());
    }
}
