<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Service\Order\Uncancel;

use Magento\Catalog\Model\Indexer\Product\Price\Processor;
use Magento\Framework\ObjectManagerInterface;
use Magento\InventorySales\Model\GetItemsToCancelFromOrderItem;
use Magento\InventorySalesApi\Api\Data\ItemToSellInterface;
use Magento\InventorySalesApi\Api\Data\ItemToSellInterfaceFactory;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterface;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterfaceFactory;
use Magento\InventorySalesApi\Api\Data\SalesEventExtensionFactory;
use Magento\InventorySalesApi\Api\Data\SalesEventInterface;
use Magento\InventorySalesApi\Api\Data\SalesEventInterfaceFactory;
use Magento\InventorySalesApi\Api\PlaceReservationsForSalesEventInterface;
use Magento\Sales\Model\Order\Item as OrderItem;
use Magento\Store\Api\WebsiteRepositoryInterface;

/**
 * The Magento_Inventory* classes are loaded through the object manager because stores can remove the
 * Multi-Source Inventory modules, and setup:di:compile fails on constructor arguments that do not exist.
 */
class OrderReservation
{
    public const EVENT_ORDER_UNCANCELED = 'order_uncanceled';

    public function __construct(
        private readonly WebsiteRepositoryInterface $websiteRepository,
        private readonly Processor $priceIndexer,
        private readonly ObjectManagerInterface $objectManager,
    ) {}

    public function execute(OrderItem $orderItem): void
    {
        $itemsToReserve = $this->getItemsToReserve($orderItem);
        if ($itemsToReserve === []) {
            return;
        }

        $this->objectManager->get(PlaceReservationsForSalesEventInterface::class)->execute(
            $itemsToReserve,
            $this->createSalesChannel($orderItem),
            $this->createSalesEvent($orderItem),
        );

        $this->priceIndexer->reindexRow($orderItem->getProductId());
    }

    /**
     * @return ItemToSellInterface[]
     */
    private function getItemsToReserve(OrderItem $orderItem): array
    {
        return array_map(
            fn (ItemToSellInterface $item): ItemToSellInterface => $this->objectManager
                ->get(ItemToSellInterfaceFactory::class)
                ->create(['sku' => $item->getSku(), 'qty' => -$item->getQuantity()]),
            $this->objectManager->get(GetItemsToCancelFromOrderItem::class)->execute($orderItem),
        );
    }

    private function createSalesChannel(OrderItem $orderItem): SalesChannelInterface
    {
        return $this->objectManager->get(SalesChannelInterfaceFactory::class)->create([
            'data' => [
                'type' => SalesChannelInterface::TYPE_WEBSITE,
                'code' => $this->websiteRepository->getById($orderItem->getStore()->getWebsiteId())->getCode(),
            ],
        ]);
    }

    private function createSalesEvent(OrderItem $orderItem): SalesEventInterface
    {
        $salesEvent = $this->objectManager->get(SalesEventInterfaceFactory::class)->create([
            'type' => self::EVENT_ORDER_UNCANCELED,
            'objectType' => SalesEventInterface::OBJECT_TYPE_ORDER,
            'objectId' => (string)$orderItem->getOrderId(),
        ]);

        $salesEvent->setExtensionAttributes(
            $this->objectManager->get(SalesEventExtensionFactory::class)->create([
                'data' => ['objectIncrementId' => (string)$orderItem->getOrder()->getIncrementId()],
            ]),
        );

        return $salesEvent;
    }
}
