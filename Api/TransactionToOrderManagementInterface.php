<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Api;

use Magento\Sales\Api\Data\OrderInterface;
use Mollie\Payment\Api\Data\TransactionToOrderInterface;

interface TransactionToOrderManagementInterface
{
    /**
     * @param int $entityId
     * @return TransactionToOrderInterface[]
     */
    public function getForOrder(int $entityId): array;
}
