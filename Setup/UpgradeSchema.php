<?php
/**
 * Copyright © 2018 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Setup;

use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\UpgradeSchemaInterface;
use Mollie\Payment\Setup\Tables\MollieOrderLines;

/**
 * Class UpgradeSchema
 *
 * @package Mollie\Payment\Setup
 */
class UpgradeSchema implements UpgradeSchemaInterface
{

    /**
     * @param SchemaSetupInterface   $setup
     * @param ModuleContextInterface $context
     *
     * @throws \Zend_Db_Exception
     */
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        if (version_compare($context->getVersion(), '1.4.0', '<')) {
            $this->createTable($setup, MollieOrderLines::getData());
        }

        if (version_compare($context->getVersion(), '1.8.0', '<')) {
            $this->addPaymentFeeColumns($setup);
        }

        if (version_compare($context->getVersion(), '1.10.0', '<')) {
            $this->addMolliePaymentTokenTable($setup);
        }

        if (version_compare($context->getVersion(), '1.10.0', '<')) {
            $this->addMolliePaymentTokenTable($setup);
        }

        if (version_compare($context->getVersion(), '1.12.0', '<')) {
            $this->addMollieCustomerTable($setup);
        }

        $setup->endSetup();
    }

    /**
     * @param SchemaSetupInterface $setup
     * @param                      $tableData
     *
     * @throws \Zend_Db_Exception
     */
    public function createTable(SchemaSetupInterface $setup, $tableData)
    {
        $connection = $setup->getConnection();
        $tableName = $setup->getTable($tableData['title']);

        if (!$connection->isTableExists($tableName)) {
            $table = $connection->newTable($tableName);
            foreach ($tableData['columns'] as $columnName => $columnData) {
                $table->addColumn($columnName, $columnData['type'], $columnData['length'], $columnData['option']);
            }
            if (!empty($tableData['indexes'])) {
                foreach ($tableData['indexes'] as $sIndex) {
                    $table->addIndex($setup->getIdxName($tableData['title'], $sIndex), $sIndex);
                }
            }
            $table->setComment($tableData['comment']);
            $connection->createTable($table);
        }
    }

    /**
     * @param SchemaSetupInterface $setup
     */
    private function addPaymentFeeColumns(SchemaSetupInterface $setup)
    {
        $connection = $setup->getConnection();

        $defintion = [
            'type' => Table::TYPE_DECIMAL,
            'length' => '12,4',
            'default' => 0.0000,
            'nullable' => true,
            'comment' => 'Mollie Payment Fee',
        ];

        foreach (['quote', 'quote_address', 'sales_order', 'sales_invoice', 'sales_creditmemo'] as $table) {
            $tableName = $setup->getTable($table);

            $connection->addColumn($tableName, 'mollie_payment_fee', $defintion);
            $connection->addColumn($tableName, 'base_mollie_payment_fee', $defintion);
            $connection->addColumn($tableName, 'mollie_payment_fee_tax', $defintion);
            $connection->addColumn($tableName, 'base_mollie_payment_fee_tax', $defintion);
        }
    }

    private function addMolliePaymentTokenTable(SchemaSetupInterface $setup)
    {
        $connection = $setup->getConnection();
        $tableName = $setup->getTable('mollie_payment_paymenttoken');

        $table = $connection->newTable($tableName);

        $table->addColumn(
            'entity_id',
            Table::TYPE_INTEGER,
            null,
            ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
            'Entity Id'
        );

        $table->addColumn(
            'cart_id',
            Table::TYPE_INTEGER,
            null,
            ['unsigned' => true, 'nullable' => false],
            'Quote Id'
        );

        $table->addColumn(
            'order_id',
            Table::TYPE_INTEGER,
            null,
            ['unsigned' => true, 'nullable' => true],
            'Order Id'
        );

        $table->addColumn(
            'token',
            Table::TYPE_TEXT,
            32,
            [],
            'Token'
        );

        $table->addForeignKey(
            $setup->getFkName('mollie_payment_token', 'cart_id', 'quote', 'entity_id'),
            'cart_id',
            $setup->getTable('quote'),
            'entity_id',
            Table::ACTION_CASCADE
        );

        $table->addForeignKey(
            $setup->getFkName('mollie_payment_token', 'order_id', 'sales_order', 'entity_id'),
            'order_id',
            $setup->getTable('sales_order'),
            'entity_id',
            Table::ACTION_CASCADE
        );

        $table->addIndex(
            $setup->getIdxName(
                $tableName,
                ['token'],
                AdapterInterface::INDEX_TYPE_UNIQUE
            ),
            ['token'],
            ['type' => AdapterInterface::INDEX_TYPE_UNIQUE]
        );

        $connection->createTable($table);
    }

    private function addMollieCustomerTable(SchemaSetupInterface $setup)
    {
        $connection = $setup->getConnection();
        $tableName = $setup->getTable('mollie_payment_customer');

        $table = $connection->newTable($tableName);

        $table->addColumn(
            'entity_id',
            Table::TYPE_INTEGER,
            null,
            ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
            'Entity Id'
        );

        $table->addColumn(
            'customer_id',
            Table::TYPE_INTEGER,
            null,
            ['unsigned' => true, 'nullable' => false],
            'Quote Id'
        );

        $table->addColumn(
            'mollie_customer_id',
            Table::TYPE_TEXT,
            null,
            ['unsigned' => true, 'nullable' => true],
            'Mollie Customer Id'
        );

        $table->addForeignKey(
            $setup->getFkName('mollie_customer', 'customer_id', 'customer_entity', 'entity_id'),
            'customer_id',
            $setup->getTable('customer_entity'),
            'entity_id',
            Table::ACTION_CASCADE
        );

        $connection->createTable($table);
    }
}
