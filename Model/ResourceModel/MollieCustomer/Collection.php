<?php
/**
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Model\ResourceModel\MollieCustomer;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            \Mollie\Payment\Model\MollieCustomer::class,
            \Mollie\Payment\Model\ResourceModel\MollieCustomer::class
        );
    }
}
