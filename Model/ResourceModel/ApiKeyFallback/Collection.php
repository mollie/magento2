<?php declare(strict_types=1);

namespace Mollie\Payment\Model\ResourceModel\ApiKeyFallback;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Mollie\Payment\Model\ApiKeyFallback;

class Collection extends AbstractCollection
{
    protected function _construct()
    {
        $this->_init(ApiKeyFallback::class, \Mollie\Payment\Model\ResourceModel\ApiKeyFallback::class);
    }
}
