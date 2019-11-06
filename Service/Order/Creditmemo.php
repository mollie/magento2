<?php
/**
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Service\Order;

use Magento\Sales\Api\Data\CreditmemoInterface;
use Magento\Sales\Api\Data\CreditmemoItemInterface;
use Magento\Sales\Api\Data\OrderItemInterface;

class Creditmemo
{
    /**
     * @param CreditmemoInterface $creditmemo
     * @return bool
     */
    public function isFullOrLastPartialCreditmemo(CreditmemoInterface $creditmemo)
    {
        /** @var CreditmemoItemInterface $item */
        foreach ($creditmemo->getAllItems() as $item) {
            /** @var OrderItemInterface $orderItem */
            $orderItem = $item->getOrderItem();
            $refundable = $orderItem->getQtyOrdered() - $orderItem->getQtyRefunded();

            if ($refundable != $item->getQty()) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param CreditmemoInterface $creditmemo
     * @return bool
     */
    public function hasItemsLeftToRefund(CreditmemoInterface $creditmemo)
    {
        /** @var OrderItemInterface $order */
        $order = $creditmemo->getOrder();

        /** @var OrderItemInterface $item */
        foreach ($order->getAllItems() as $item) {
            $refundable = $item->getQtyOrdered() - $item->getQtyRefunded();

            if ($refundable) {
                return true;
            }
        }

        return false;
    }
}
