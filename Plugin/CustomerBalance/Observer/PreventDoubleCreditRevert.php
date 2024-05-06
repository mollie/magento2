<?php

declare(strict_types=1);

namespace Mollie\Payment\Plugin\CustomerBalance\Observer;

use Magento\Framework\Event\Observer;
use Magento\Sales\Api\Data\OrderInterface;

class PreventDoubleCreditRevert
{
    public function aroundExecute($subject, callable $proceed, Observer $observer)
    {
        $order = $observer->getData('order');
        if (!$order ||
            !$order instanceof OrderInterface ||
            !$order->getPayment()
        ) {
            return $proceed($observer);
        }

        if ($observer->getEvent()->getName() == 'restore_quote' &&
            $this->isMollieOrder($order)
        ) {
            return $subject;
        }

        return $proceed($observer);
    }

    private function isMollieOrder(OrderInterface $order): bool
    {
        $payment = $order->getPayment();

        return strstr($payment->getMethod(), 'mollie_methods_') !== false;
    }
}
