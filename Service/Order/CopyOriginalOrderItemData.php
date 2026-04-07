<?php
/**
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Service\Order;

use Magento\Framework\DataObject\Copy;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Sales\Api\Data\OrderInterface;

class CopyOriginalOrderItemData
{
    /**
     * @var Copy
     */
    private $objectCopyService;

    public function __construct(
        Copy $objectCopyService
    ) {
        $this->objectCopyService = $objectCopyService;
    }

    public function execute(OrderInterface $originalOrder, CartInterface $quote): void
    {
        $orderItemMap = [];
        foreach ($originalOrder->getItems() as $orderItem) {
            if ($orderItem->getParentItemId()) {
                continue;
            }

            $key = $orderItem->getProductId() . ':' . $orderItem->getSku();
            $orderItemMap[$key] = $orderItem;
        }

        foreach ($quote->getAllVisibleItems() as $quoteItem) {
            $key = $quoteItem->getProductId() . ':' . $quoteItem->getSku();
            if (!isset($orderItemMap[$key])) {
                continue;
            }

            $this->objectCopyService->copyFieldsetToTarget(
                'sales_convert_order_item',
                'to_quote_item',
                $orderItemMap[$key],
                $quoteItem
            );
        }

        $quote->setTotalsCollectedFlag(false);
        $quote->collectTotals();
    }
}
