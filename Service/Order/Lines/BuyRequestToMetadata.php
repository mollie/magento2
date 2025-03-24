<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Service\Order\Lines;

use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderItemInterface;
use Mollie\Payment\Service\Order\Lines\Processor\ProcessorInterface;

class BuyRequestToMetadata implements ProcessorInterface
{
    public function process($orderLine, OrderInterface $order, ?OrderItemInterface $orderItem = null): array
    {
        if (!$orderItem) {
            return $orderLine;
        }

        $buyRequest = $orderItem->getProductOptionByCode('info_buyRequest');
        if (!$buyRequest || !isset($buyRequest['mollie_metadata'])) {
            return $orderLine;
        }

        $orderLine['metadata'] = $buyRequest['mollie_metadata'];

        return $orderLine;
    }
}
