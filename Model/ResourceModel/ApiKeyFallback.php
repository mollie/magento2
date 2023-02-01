<?php declare(strict_types=1);

namespace Mollie\Payment\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class ApiKeyFallback extends AbstractDb
{
    /** @var string Main table name */
    const MAIN_TABLE = 'mollie_apikey_fallback';

    /** @var string Main table primary key field name */
    const ID_FIELD_NAME = 'entity_id';

    protected function _construct()
    {
        $this->_init(self::MAIN_TABLE, self::ID_FIELD_NAME);
    }
}
