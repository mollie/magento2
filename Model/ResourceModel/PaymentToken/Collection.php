<?php
/**
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Model\ResourceModel\PaymentToken;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            \Mollie\Payment\Model\PaymentToken::class,
            \Mollie\Payment\Model\ResourceModel\PaymentToken::class
        );
    }
}
