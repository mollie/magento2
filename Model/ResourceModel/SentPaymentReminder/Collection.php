<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Model\ResourceModel\SentPaymentReminder;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Mollie\Payment\Model\SentPaymentReminder;

class Collection extends AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'entity_id';

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            SentPaymentReminder::class,
            \Mollie\Payment\Model\ResourceModel\SentPaymentReminder::class,
        );
    }
}
