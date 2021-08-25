<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Plugin\Sales;

use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Sales\Api\Data\OrderSearchResultInterface;

class AddRecurringData
{
    public function afterGetList($subject, OrderSearchResultInterface $result)
    {
        return $this->handleItems($result);
    }

    private function handleItems(OrderSearchResultInterface $result): OrderSearchResultInterface
    {
        foreach ($result->getItems() as $item) {
            foreach ($item->getItems() as $orderItem) {
                $this->addProductOptionsToExtensionAttributes($orderItem);
            }
        }

        return $result;
    }

    private function addProductOptionsToExtensionAttributes(OrderItemInterface $orderItem): void
    {
        $buyRequest = $orderItem->getProductOptionByCode('info_buyRequest');

        if (!$buyRequest || !isset($buyRequest['recurring_metadata'])) {
            return;
        }

        $orderItem->getExtensionAttributes()->setMollieRecurringType($buyRequest['purchase']);
        $orderItem->getExtensionAttributes()->setMollieRecurringData([$buyRequest['recurring_metadata']]);
    }
}
