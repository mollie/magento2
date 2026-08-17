<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Setup\Patch\Schema;

use Magento\Framework\Setup\Patch\SchemaPatchInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Mollie\Payment\Model\ResourceModel\TransactionToOrder;

/**
 * The unique key lives here instead of in db_schema.xml because declarative schema is applied before any patch
 * runs, so a declared key would be added while the duplicates are still present and abort setup:upgrade.
 */
class DeduplicateTransactionToOrder implements SchemaPatchInterface
{
    public const INDEX_NAME = 'MOLLIE_PAYMENT_TRANSACTION_TO_ORDER_TRANSACTION_ID_ORDER_ID';

    public function __construct(
        private readonly SchemaSetupInterface $setup,
    ) {}

    public function apply(): self
    {
        $connection = $this->setup->getConnection();
        $table = $this->setup->getTable(TransactionToOrder::MAIN_TABLE);

        if (!$connection->isTableExists($table)) {
            return $this;
        }

        $this->removeDuplicates($table);

        if (!$this->hasUniqueIndex($table)) {
            $connection->query(sprintf(
                'ALTER TABLE %s ADD UNIQUE KEY %s (transaction_id, order_id)',
                $connection->quoteIdentifier($table),
                $connection->quoteIdentifier(self::INDEX_NAME),
            ));
        }

        return $this;
    }

    private function removeDuplicates(string $table): void
    {
        $connection = $this->setup->getConnection();
        $quotedTable = $connection->quoteIdentifier($table);

        $connection->query(sprintf(
            'DELETE duplicate FROM %s AS duplicate
                INNER JOIN %s AS original
                    ON duplicate.transaction_id = original.transaction_id
                    AND duplicate.order_id = original.order_id
                    AND duplicate.entity_id > original.entity_id',
            $quotedTable,
            $quotedTable,
        ));
    }

    private function hasUniqueIndex(string $table): bool
    {
        $connection = $this->setup->getConnection();

        foreach ($connection->getIndexList($table) as $index) {
            if (($index['KEY_NAME'] ?? '') === self::INDEX_NAME) {
                return true;
            }
        }

        return false;
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
