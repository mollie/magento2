<?php

namespace Mollie\Payment\Observer\SalesOrderPaymentPlaceEnd;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\StatusResolver;
use Mollie\Payment\Model\Mollie;

class SetOrderStateToPendingPayment implements ObserverInterface
{
    /**
     * @var StatusResolver
     */
    private $statusResolver;

    public function __construct(
        StatusResolver $statusResolver
    ) {
        $this->statusResolver = $statusResolver;
    }

    public function execute(Observer $observer)
    {
        /** @var PaymentInterface $payment */
        $payment = $observer->getData('payment');

        /** @var OrderInterface $order */
        $order = $payment->getOrder();

        if (substr($payment->getMethod(), 0, strlen('mollie_methods_')) !== 'mollie_methods_' ||
            $order->getState() != Order::STATE_PAYMENT_REVIEW
        ) {
            return;
        }

        $order->setState(Order::STATE_PENDING_PAYMENT);
        $status = $this->statusResolver->getOrderStatusByState($order, Order::STATE_PENDING_PAYMENT);
        $order->setStatus($status);
    }
}
