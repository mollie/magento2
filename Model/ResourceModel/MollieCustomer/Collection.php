<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Model\ResourceModel\MollieCustomer;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Mollie\Payment\Model\MollieCustomer;

class Collection extends AbstractCollection
{
    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            MollieCustomer::class,
            \Mollie\Payment\Model\ResourceModel\MollieCustomer::class,
        );
    }
}
