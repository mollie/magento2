<?php

namespace Mollie\Payment\Service\Mollie\Order;

use Magento\Sales\Api\Data\OrderInterface;

class CreateInvoiceOnShipment
{
    public function execute(OrderInterface $order): bool
    {
        $methodCode = $order->getPayment()->getMethod();
        if (in_array($methodCode, [
            'mollie_methods_billie',
            'mollie_methods_klarna',
            'mollie_methods_klarnapaylater',
            'mollie_methods_klarnapaynow',
            'mollie_methods_klarnasliceit',
            'mollie_methods_in3',
        ])) {
            return true;
        }

        return false;
    }
}
