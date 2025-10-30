<?php

/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Api;

use Mollie\Payment\Api\Data\TransactionToOrderInterface;

interface TransactionToOrderManagementInterface
{
    /**
     * @param int $entityId
     * @return TransactionToOrderInterface[]
     */
    public function getForOrder(int $entityId): array;
}
