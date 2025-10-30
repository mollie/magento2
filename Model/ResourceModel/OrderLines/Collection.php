<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

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
    public function _construct(): void
    {
        $this->_init(
            'Mollie\Payment\Model\OrderLines',
            'Mollie\Payment\Model\ResourceModel\OrderLines',
        );
    }
}
