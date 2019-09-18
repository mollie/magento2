<?php
/**
 * Copyright © 2018 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Setup;

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

        if (version_compare($context->getVersion(), '1.7.0', '<')) {
            $this->addPaymentFeeColumns($setup);
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
}
