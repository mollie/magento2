<?php

namespace Mollie\Payment\Tests\Integration\Setup;

use Magento\Framework\App\ResourceConnection;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;

class SchemaTest extends TestCase
{
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
        $om = ObjectManager::getInstance();
        /** @var ResourceConnection $resource */
        $resource = $om->get(ResourceConnection::class);
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
}