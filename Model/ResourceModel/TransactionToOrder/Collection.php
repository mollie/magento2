<?php 

declare(strict_types=1);

namespace Mollie\Payment\Model\ResourceModel\TransactionToOrder;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Mollie\Payment\Model\TransactionToOrder;

class Collection extends AbstractCollection
{
    protected function _construct()
    {
        $this->_init(TransactionToOrder::class, \Mollie\Payment\Model\ResourceModel\TransactionToOrder::class);
    }
}
