<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Observer\SalesOrderPlaceBefore;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Mollie\Payment\Config;
use Mollie\Payment\Service\Order\DeletePaymentReminder;

class RemovePendingPaymentReminders implements ObserverInterface
{
    public function __construct(
        private Config $config,
        private DeletePaymentReminder $deletePaymentReminder
    ) {}

    /**
     * Remove any pending payment reminders. This is to prevent that payment reminders get send if the order was paid
     * by a payment method that is not from Mollie.
     *
     * @param Observer $observer
     */
    public function execute(Observer $observer): void
    {
        /** @var OrderInterface $order */
        $order = $observer->getData('order');

        $email = $order->getCustomerEmail();
        if (!$this->config->automaticallySendSecondChanceEmails(storeId($order->getStoreId())) || !$email) {
            return;
        }

        $this->deletePaymentReminder->delete($order->getCustomerId() ?: $order->getCustomerEmail());
    }
}
