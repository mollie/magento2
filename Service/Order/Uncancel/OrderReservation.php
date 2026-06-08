<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Service\Order\Uncancel;

use Magento\Catalog\Model\Indexer\Product\Price\Processor;
use Magento\Framework\ObjectManagerInterface;
use Magento\InventorySales\Model\PlaceReservationsForSalesEvent;
use Magento\InventorySales\Model\SalesEvent;
use Magento\InventorySalesApi\Api\Data\ItemToSellInterface;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterface;
use Magento\InventorySalesApi\Api\Data\SalesEventInterface;
use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Store\Api\WebsiteRepositoryInterface;

/**
 * Class OrderReservation
 * @package Mollie\Payment\Service\Order\Uncancel
 *
 * This class has also some hidden dependencies not listed in the constuctor:
 * - \Magento\InventorySalesApi\Api\Data\ItemToSellInterfac
 * - \Magento\InventorySalesApi\Api\Data\SalesChannelInterface
 * - \Magento\InventorySalesApi\Api\Data\SalesEventInterface
 * - \Magento\InventorySalesApi\Api\PlaceReservationsForSalesEventInterface
 *
 * This class is only loaded when MSI is enabled, but when setup:di:compile runs it will still fail on thoses classes
 * in Magento 2.2 because they don't exists. That's why they are loaded using the object manager.
 */
class OrderReservation
{
    public function __construct(
        private WebsiteRepositoryInterface $websiteRepository,
        private Processor $priceIndexer,
        private ObjectManagerInterface $objectManager
    ) {}

    public function execute(OrderItemInterface $orderItem): void
    {
        $websiteId = $orderItem->getStore()->getWebsiteId();
        $websiteCode = $this->websiteRepository->getById($websiteId)->getCode();
        $salesChannel = $this->objectManager->create(SalesChannelInterface::class, [
            'data' => [
                'type' => SalesChannelInterface::TYPE_WEBSITE,
                'code' => $websiteCode,
            ],
        ]);

        $salesEvent = $this->objectManager->create(SalesEvent::class, [
            'type' => 'order_uncanceled',
            'objectType' => SalesEventInterface::OBJECT_TYPE_ORDER,
            'objectId' => (string) $orderItem->getOrderId(),
        ]);

        $placeReservationsForSalesEvent = $this->objectManager->create(PlaceReservationsForSalesEvent::class);
        $placeReservationsForSalesEvent->execute($this->getItemsToUncancel($orderItem), $salesChannel, $salesEvent);

        $this->priceIndexer->reindexRow($orderItem->getProductId());
    }

    /**
     * @return mixed[]
     */
    private function getItemsToUncancel(OrderItemInterface $orderItem): array
    {
        $itemsToUncancel = [];
        $itemsToUncancel[] = $this->objectManager->create(ItemToSellInterface::class, [
            'sku' => $orderItem->getSku(),
            // 0 - X = make it negative.
            'qty' => 0 - $orderItem->getQtyCanceled(),
        ]);

        if ($orderItem->getHasChildren()) {
            foreach ($itemsToUncancel as $item) {
                $itemsToUncancel[] = $this->objectManager->create(ItemToSellInterface::class, [
                    'sku' => $orderItem->getSku(),
                    // 0 - X = make it negative.
                    'qty' => 0 - $orderItem->getQtyCanceled(),
                ]);
            }
        }

        return $itemsToUncancel;
    }
}
