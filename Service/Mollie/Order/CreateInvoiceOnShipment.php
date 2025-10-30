<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Service\Mollie\Order;

use Magento\Sales\Api\Data\OrderInterface;

class CreateInvoiceOnShipment
{
    public function execute(OrderInterface $order): bool
    {
        $methodCode = $order->getPayment()->getMethod();
        if (
            in_array($methodCode, [
            'mollie_methods_billie',
            'mollie_methods_in3',
            'mollie_methods_klarna',
            'mollie_methods_riverty',
            ])
        ) {
            return true;
        }

        return false;
    }
}
