<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 *  See COPYING.txt for license details.
 */
namespace Mollie\Payment\Model\ResourceModel\SentPaymentReminder;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
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
            \Mollie\Payment\Model\SentPaymentReminder::class,
            \Mollie\Payment\Model\ResourceModel\SentPaymentReminder::class
        );
    }
}
