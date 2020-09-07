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
use Mollie\Payment\Service\Order\DeletePaymentReminder;

class RemoveCompletedOrdersFromPendingPaymentTable implements ObserverInterface
{
    /**
     * @var DeletePaymentReminder
     */
    private $deletePaymentReminder;

    public function __construct(
        DeletePaymentReminder $deletePaymentReminder
    ) {
        $this->deletePaymentReminder = $deletePaymentReminder;
    }

    public function execute(Observer $observer)
    {
        /** @var OrderInterface $order */
        $order = $observer->getData('order');

        if ($order->getState() != Order::STATE_PROCESSING) {
            return;
        }

        $email = $order->getCustomerEmail();
        $this->deletePaymentReminder->byEmail($email);
    }
}