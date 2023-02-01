<?php

declare(strict_types=1);

namespace Mollie\Payment\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class TransactionToOrder extends AbstractDb
{
    public const MAIN_TABLE = 'mollie_payment_transaction_to_order';

    public const ID_FIELD_NAME = 'entity_id';

    protected function _construct()
    {
        $this->_init(self::MAIN_TABLE, self::ID_FIELD_NAME);
    }
}
