<?php
/**
 * Copyright © 2018 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Mollie\Payment\Setup\Tables\MollieOrderLines;

/**
 * Class InstallSchema
 *
 * @package Mollie\Payment\Setup
 */
class InstallSchema implements InstallSchemaInterface
{

    /**
     * @param SchemaSetupInterface   $installer
     * @param ModuleContextInterface $context
     *
     * @throws \Zend_Db_Exception
     */
    public function install(SchemaSetupInterface $installer, ModuleContextInterface $context)
    {
        $installer->startSetup();
        $this->createTable($installer, MollieOrderLines::getData());
        $installer->endSetup();
    }

    /**
     * @param SchemaSetupInterface $installer
     * @param                      $tableData
     *
     * @throws \Zend_Db_Exception
     */
    public function createTable(SchemaSetupInterface $installer, $tableData)
    {
        $connection = $installer->getConnection();
        $tableName = $installer->getTable($tableData['title']);

        if (!$connection->isTableExists($tableName)) {
            $table = $connection->newTable($tableName);
            foreach ($tableData['columns'] as $columnName => $columnData) {
                $table->addColumn($columnName, $columnData['type'], $columnData['length'], $columnData['option']);
            }
            if (!empty($tableData['indexes'])) {
                foreach ($tableData['indexes'] as $sIndex) {
                    $table->addIndex($installer->getIdxName($tableData['title'], $sIndex), $sIndex);
                }
            }
            $table->setComment($tableData['comment']);
            $connection->createTable($table);
        }
    }
}
