<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Model;

use Magento\Sales\Api\Data\OrderItemInterface;
use Mollie\Payment\Api\Data\OrderItemMetadataInterface;

class OrderItemMetadata implements OrderItemMetadataInterface
{
    /**
     * @var OrderItemInterface
     */
    private $orderItem;

    public function __construct(
        OrderItemInterface $orderItem
    ) {
        $this->orderItem = $orderItem;
    }

    public function getOrderId(): int
    {
        return $this->orderItem->getOrderId();
    }

    public function getOrderItemId(): int
    {
        return $this->orderItem->getItemId();
    }

    public function getMetadata(): string
    {
        $buyRequest = $this->orderItem->getProductOptionByCode('info_buyRequest');

        if ($buyRequest && array_key_exists('mollie_metadata', $buyRequest)) {
            return json_encode($buyRequest['mollie_metadata']);
        }

        return json_encode('null');
    }
}
