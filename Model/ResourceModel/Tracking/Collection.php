<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Model\ResourceModel\Tracking;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Mollie\Payment\Model\Tracking;

class Collection extends AbstractCollection
{
    protected function _construct()
    {
        $this->_init(
            Tracking::class,
            \Mollie\Payment\Model\ResourceModel\Tracking::class,
        );
    }
}
