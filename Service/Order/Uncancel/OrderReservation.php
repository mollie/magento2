<?php

namespace Mollie\Payment\Service\Order\Uncancel;

use Magento\Catalog\Model\Indexer\Product\Price\Processor;
use Magento\Framework\App\ObjectManager;
use Magento\InventorySalesApi\Api\Data\ItemToSellInterfaceFactory;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterface;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterfaceFactory;
use Magento\InventorySalesApi\Api\Data\SalesEventInterface;
use Magento\InventorySalesApi\Api\Data\SalesEventInterfaceFactory;
use Magento\InventorySalesApi\Api\PlaceReservationsForSalesEventInterface;
use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Store\Api\WebsiteRepositoryInterface;

class OrderReservation
{
    /**
     * @var WebsiteRepositoryInterface
     */
    private $websiteRepository;

    /**
     * @var Processor
     */
    private $priceIndexer;

    /**
     * @var ObjectManager
     */
    private $objectManager;

    public function __construct(
        WebsiteRepositoryInterface $websiteRepository,
        Processor $priceIndexer,
        ObjectManager $objectManager
    ) {
        $this->websiteRepository = $websiteRepository;
        $this->priceIndexer = $priceIndexer;
        $this->objectManager = $objectManager;
    }

    public function execute(OrderItemInterface $orderItem)
    {
        $websiteId = $orderItem->getStore()->getWebsiteId();
        $websiteCode = $this->websiteRepository->getById($websiteId)->getCode();
        $salesChannel = $this->objectManager->create(SalesChannelInterfaceFactory::class, [
            'data' => [
                'type' => SalesChannelInterface::TYPE_WEBSITE,
                'code' => $websiteCode
            ]
        ]);

        $salesEvent = $this->objectManager->create(SalesEventFactory::class, [
            'type' => 'order_uncanceled',
            'objectType' => SalesEventInterface::OBJECT_TYPE_ORDER,
            'objectId' => (string)$orderItem->getOrderId()
        ]);

        $placeReservationsForSalesEvent = $this->objectManager->create(PlaceReservationsForSalesEvent::class);
        $placeReservationsForSalesEvent->execute($this->getItemsToUncancel($orderItem), $salesChannel, $salesEvent);

        $this->priceIndexer->reindexRow($orderItem->getProductId());
    }

    private function getItemsToUncancel(OrderItemInterface $orderItem)
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
