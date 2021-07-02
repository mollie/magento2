<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Service\Order;

use Magento\Sales\Api\Data\OrderInterface;

class OrderContainsSubscriptionProduct
{
    public function check(OrderInterface $order): bool
    {
        foreach ($order->getItems() as $item) {
            if ($item->getProductOptionByCode('info_buyRequest') &&
                $this->buyRequestContainsSubscriptionProduct($item->getProductOptionByCode('info_buyRequest'))
            ) {
                return true;
            }
        }

        return false;
    }

    private function buyRequestContainsSubscriptionProduct(array $buyRequest): bool
    {
        if (!isset($buyRequest['mollie_metadata']) ||
            !isset($buyRequest['mollie_metadata']['is_recurring'])) {
            return false;
        }

        return (bool)$buyRequest['mollie_metadata']['is_recurring'];
    }
}
