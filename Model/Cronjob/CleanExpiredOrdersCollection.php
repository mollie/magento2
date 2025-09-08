<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Model\Cronjob;

use Magento\Sales\Model\ResourceModel\Order\Collection;
use Mollie\Payment\Model\Methods\Banktransfer;

class CleanExpiredOrdersCollection extends Collection
{
    /**
     * Do not auto cancel pending banktransfer orders. They may take a few days before they receive an update.
     */
    public function getAllIds($limit = null, $offset = null): array
    {
        $this->getSelect()
            ->join(
                ['payment' => $this->getTable('sales_order_payment')],
                'main_table.entity_id = payment.parent_id',
                []
            )
            ->where('payment.method != ?', Banktransfer::CODE);

        return parent::getAllIds($limit, $offset);
    }
}
