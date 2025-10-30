<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Observer\SalesOrderPaymentPlaceEnd;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\StatusResolver;

class SetOrderStateToPendingPayment implements ObserverInterface
{
    public function __construct(
        private StatusResolver $statusResolver
    ) {}

    public function execute(Observer $observer): void
    {
        /** @var PaymentInterface $payment */
        $payment = $observer->getData('payment');

        /** @var OrderInterface $order */
        $order = $payment->getOrder();

        if (
            substr($payment->getMethod(), 0, strlen('mollie_methods_')) !== 'mollie_methods_' ||
            $order->getState() != Order::STATE_PAYMENT_REVIEW
        ) {
            return;
        }

        $order->setState(Order::STATE_PENDING_PAYMENT);
        $status = $this->statusResolver->getOrderStatusByState($order, Order::STATE_PENDING_PAYMENT);
        $order->setStatus($status);
    }
}
