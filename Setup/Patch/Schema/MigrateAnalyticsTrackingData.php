<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Setup\Patch\Schema;

use Magento\Framework\Setup\Patch\SchemaPatchInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

class MigrateAnalyticsTrackingData implements SchemaPatchInterface
{
    private const LEGACY_TABLE = 'mollie_analytics_analytics';
    private const TARGET_TABLE = 'mollie_payment_tracking';

    public function __construct(
        private readonly SchemaSetupInterface $setup,
    ) {}

    public function apply(): self
    {
        $connection = $this->setup->getConnection();

        $legacyTable = $this->setup->getTable(self::LEGACY_TABLE);
        if (!$connection->isTableExists($legacyTable)) {
            return $this;
        }

        $targetTable = $this->setup->getTable(self::TARGET_TABLE);

        $select = $connection->select()->from(
            $legacyTable,
            ['cart_id', 'client_id', 'created_at'],
        );

        $rows = [];
        foreach ($connection->fetchAll($select) as $row) {
            $cartId = $row['cart_id'] ?? null;
            $clientId = $row['client_id'] ?? null;
            if (!$cartId || $clientId === null || $clientId === '') {
                continue;
            }

            $rows[] = [
                'cart_id' => (int) $cartId,
                'tracking_data' => json_encode(['clientId' => (string) $clientId]),
                'created_at' => $row['created_at'] ?? null,
            ];
        }

        if ($rows) {
            $connection->insertMultiple($targetTable, $rows);
        }

        $connection->dropTable($legacyTable);

        return $this;
    }

    public static function getDependencies(): array
    {
        return [];
    }

    public function getAliases(): array
    {
        return [];
    }
}
