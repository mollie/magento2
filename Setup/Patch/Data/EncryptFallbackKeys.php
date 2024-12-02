<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Setup\Patch\Data;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class EncryptFallbackKeys implements DataPatchInterface
{
    /**
     * @var EncryptorInterface
     */
    private $encryptor;
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    public function __construct(
        EncryptorInterface $encryptor,
        ResourceConnection $resourceConnection
    ) {
        $this->encryptor = $encryptor;
        $this->resourceConnection = $resourceConnection;
    }

    public function apply()
    {
        $connection = $this->resourceConnection->getConnection();
        $tableName = $connection->getTableName('mollie_payment_apikey_fallback');

        if (!$connection->isTableExists($tableName)) {
            return $this;
        }

        $query = 'select * from ' . $tableName;
        $result = $connection->fetchAll($query);
        foreach ($result as $row) {
            $start = substr($row['api_key'], 0, 4);
            if (!in_array($start, ['live', 'test'])) {
                continue;
            }

            $encrypted = $this->encryptor->encrypt($row['api_key']);
            $connection->update($tableName, ['api_key' => $encrypted], ['id = ?' => $row['id']]);
        }

        return $this;
    }

    public static function getDependencies()
    {
        return [];
    }

    public function getAliases()
    {
        return [];
    }
}
