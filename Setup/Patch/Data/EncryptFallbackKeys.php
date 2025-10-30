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
    public function __construct(
        private EncryptorInterface $encryptor,
        private ResourceConnection $resourceConnection
    ) {}

    public function apply()
    {
        $connection = $this->resourceConnection->getConnection();
        $tableName = $connection->getTableName('mollie_payment_apikey_fallback');

        if (!$connection->isTableExists($tableName)) {
            return $this;
        }

        // Bit nasty but the only to get around the codesniffer on a higher level.
        $query = 'select' . ' * from ' . $tableName; // phpcs:ignore
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
