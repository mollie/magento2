<?php
/**
 *  Copyright © 2018 Magmodules.eu. All rights reserved.
 *  See COPYING.txt for license details.
 */

namespace Mollie\Payment\Model\ResourceModel\OrderLines;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

/**
 * Class Collection
 *
 * @package Mollie\Payment\Model\ResourceModel\OrderLines
 */
class Collection extends AbstractCollection
{

    /**
     * @var string
     */
    protected $_idFieldName = 'id';

    /**
     *
     */
    public function _construct()
    {
        $this->_init(
            'Mollie\Payment\Model\OrderLines',
            'Mollie\Payment\Model\ResourceModel\OrderLines'
        );
    }
}
