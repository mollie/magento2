<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Model;

use Magento\Framework\Api\ExtensionAttribute\JoinProcessorInterface;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Sales\Api\Data\OrderSearchResultInterface;
use Magento\Sales\Api\Data\OrderSearchResultInterfaceFactory;
use Mollie\Payment\Api\MagentoOrderRepositoryInterface;
use Zend_Db_Expr;

class MagentoOrderRepository implements MagentoOrderRepositoryInterface
{
    public function __construct(
        private JoinProcessorInterface $extensionAttributesJoinProcessor,
        private CollectionProcessorInterface $collectionProcessor,
        private OrderSearchResultInterfaceFactory $searchResultsFactory
    ) {}

    /**
     * @param SearchCriteriaInterface $searchCriteria
     * @return OrderSearchResultInterface
     */
    public function getRecurringOrders(SearchCriteriaInterface $searchCriteria)
    {
        /** @var OrderSearchResultInterface $searchResults */
        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($searchCriteria);

        $subquery = new Zend_Db_Expr('(select distinct order_id from sales_order_item where product_options like \'%is_recurring%\')');
        $searchResults->getSelect()->join(['t' => $subquery], 'main_table.entity_id = t.order_id');

        $this->extensionAttributesJoinProcessor->process($searchResults);

        $this->collectionProcessor->process($searchCriteria, $searchResults);

        foreach ($searchResults->getItems() as $item) {
            foreach ($item->getItems() as $orderItem) {
                $this->addProductOptionsToExtensionAttributes($orderItem);
            }
        }

        return $searchResults;
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
