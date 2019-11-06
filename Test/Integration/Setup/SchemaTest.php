<?php

namespace Mollie\Payment\Test\Integration\Setup;

use Magento\Framework\App\ResourceConnection;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;

class SchemaTest extends TestCase
{
    /**
     * @var \Magento\Framework\App\ObjectManager
     */
    private $objectManager;

    protected function setUp()
    {
        $this->objectManager = ObjectManager::getInstance();
    }

    public function addedColumnsHaveIndexesProvider()
    {
        return [
            ['sales_order', 'mollie_transaction_id'],
            ['sales_shipment', 'mollie_shipment_id'],
        ];
    }

    /**
     * @dataProvider addedColumnsHaveIndexesProvider
     */
    public function testAddedColumnsHaveIndexes($tableName, $columnName)
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

    public function thePaymentFeeColumnsExistsProvider()
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
    public function testThePaymentFeeColumnsExists($tableName)
    {
        /** @var ResourceConnection $resource */
        $resource = $this->objectManager->get(ResourceConnection::class);
        $connection = $resource->getConnection();

        $tableName = $resource->getTableName($tableName);
        $columns = $connection->fetchAll('SHOW COLUMNS FROM ' . $tableName);

        $columns = array_map( function ($column) {
            return $column['Field'];
        }, $columns);

        $this->assertTrue(in_array('mollie_payment_fee', $columns));
        $this->assertTrue(in_array('base_mollie_payment_fee', $columns));
        $this->assertTrue(in_array('mollie_payment_fee_tax', $columns));
        $this->assertTrue(in_array('base_mollie_payment_fee_tax', $columns));
    }
}
