<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Model\ResourceModel\PaymentToken;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Mollie\Payment\Model\PaymentToken;

class Collection extends AbstractCollection
{
    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            PaymentToken::class,
            \Mollie\Payment\Model\ResourceModel\PaymentToken::class,
        );
    }
}
