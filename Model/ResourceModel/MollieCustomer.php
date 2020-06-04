<?php
/**
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class MollieCustomer extends AbstractDb
{
    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init('mollie_payment_customer', 'entity_id');
    }
}
