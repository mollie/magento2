<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Integration\Setup;

use Magento\Framework\App\ResourceConnection;
use Mollie\Payment\Test\Integration\IntegrationTestCase;

class SchemaTest extends IntegrationTestCase
{
    public function addedColumnsHaveIndexesProvider(): array
    {
        return [
            ['sales_order', 'mollie_transaction_id'],
            ['sales_shipment', 'mollie_shipment_id'],
        ];
    }

    /**
     * @dataProvider addedColumnsHaveIndexesProvider
     */
    public function testAddedColumnsHaveIndexes(string $tableName, string $columnName): void
    {
        /** @var ResourceConnection $resource */
        $resource = $this->objectManager->get(ResourceConnection::class);
        $connection = $resource->getConnection();

        $tableName = $resource->getTableName($tableName);
        $indexes = $connection->fetchAll('SHOW INDEX FROM ' . $tableName);

        foreach ($indexes as $index) {
            $indexColumnName = $index['Column_name'];

            if ($indexColumnName == $columnName) {
                return;
            }
        }

        $this->fail('There was no index found for `' . $columnName . '` in `' . $tableName . '`');
    }

    public function thePaymentFeeColumnsExistsProvider(): array
    {
        return [
            ['quote'],
            ['quote_address'],
            ['sales_order'],
            ['sales_invoice'],
            ['sales_creditmemo'],
        ];
    }

    /**
     * @dataProvider thePaymentFeeColumnsExistsProvider
     */
    public function testThePaymentFeeColumnsExists(string $tableName): void
    {
        /** @var ResourceConnection $resource */
        $resource = $this->objectManager->get(ResourceConnection::class);
        $connection = $resource->getConnection();

        $tableName = $resource->getTableName($tableName);
        $columns = $connection->fetchAll('SHOW COLUMNS FROM ' . $tableName);

        $columns = array_map(function (array $column) {
            return $column['Field'];
        }, $columns);

        $this->assertTrue(
            in_array('mollie_payment_fee', $columns),
            sprintf('The "%s" table should have the "mollie_payment_fee" column', $tableName),
        );
        $this->assertTrue(
            in_array('base_mollie_payment_fee', $columns),
            sprintf('The "%s" table should have the "base_mollie_payment_fee" column', $tableName),
        );
        $this->assertTrue(
            in_array('mollie_payment_fee_tax', $columns),
            sprintf('The "%s" table should have the "mollie_payment_fee_tax" column', $tableName),
        );
        $this->assertTrue(
            in_array('base_mollie_payment_fee_tax', $columns),
            sprintf('The "%s" table should have the "base_mollie_payment_fee_tax" column', $tableName),
        );
    }

    public function tableExistsDataProvider(): array
    {
        return [
            ['mollie_order_lines'],
            ['mollie_payment_paymenttoken'],
            ['mollie_payment_customer'],
            ['mollie_pending_payment_reminder'],
            ['mollie_sent_payment_reminder'],
        ];
    }

    /**
     * @dataProvider tableExistsDataProvider
     * @param string $table
     */
    public function testTableExists(string $table): void
    {
        /** @var ResourceConnection $resource */
        $resource = $this->objectManager->get(ResourceConnection::class);
        $connection = $resource->getConnection();

        $tableName = $resource->getTableName($table);
        $rows = $connection->fetchAll('show tables like \'' . $tableName . '\'');

        $this->assertEquals(1, count($rows));
    }

    public function tableHasAllRequiredColumns(): array
    {
        return [
            [
                'table' => 'mollie_order_lines',
                'columns' => [
                    'id',
                    'item_id',
                    'line_id',
                    'order_id',
                    'type',
                    'sku',
                    'qty_ordered',
                    'qty_paid',
                    'qty_canceled',
                    'qty_shipped',
                    'qty_refunded',
                    'unit_price',
                    'discount_amount',
                    'total_amount',
                    'vat_rate',
                    'vat_amount',
                    'currency',
                    'created_at',
                    'updated_at',
                ],
            ],
            [
                'table' => 'mollie_payment_paymenttoken',
                'columns' => [
                    'entity_id',
                    'cart_id',
                    'order_id',
                    'token',
                ],
            ],
            [
                'table' => 'mollie_payment_customer',
                'columns' => [
                    'entity_id',
                    'customer_id',
                    'mollie_customer_id',
                ],
            ],
            [
                'table' => 'mollie_pending_payment_reminder',
                'columns' => [
                    'entity_id',
                    'order_id',
                    'created_at',
                ],
            ],
            [
                'table' => 'mollie_sent_payment_reminder',
                'columns' => [
                    'entity_id',
                    'order_id',
                    'created_at',
                ],
            ],
        ];
    }

    /**
     * @dataProvider tableHasAllRequiredColumns
     * @param string $table
     * @param array $expectedColumns
     */
    public function testTableHasAllRequiredColumns(string $table, array $expectedColumns): void
    {
        /** @var ResourceConnection $resource */
        $resource = $this->objectManager->get(ResourceConnection::class);
        $connection = $resource->getConnection();

        $tableName = $resource->getTableName($table);
        $rows = $connection->fetchAll('DESCRIBE ' . $tableName);

        $columns = array_map(function (array $column) {
            return $column['Field'];
        }, $rows);

        foreach ($expectedColumns as $column) {
            $this->assertTrue(
                in_array($column, $columns),
                sprintf('mollie_order_lines should contain the `%s` column', $column),
            );
        }
    }
}
