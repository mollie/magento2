<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Api;

use Magento\Framework\Api\SearchCriteriaInterface;

interface MagentoOrderRepositoryInterface
{
    /**
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \Magento\Sales\Api\Data\OrderSearchResultInterface
     */
    public function getRecurringOrders(SearchCriteriaInterface $searchCriteria);
}
