<?php
/**
 * Copyright © 2018 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Setup\Tables;

use Magento\Framework\DB\Ddl\Table;

/**
 * Class MollieOrderLines
 *
 * @package Mollie\Payment\Setup\Tables
 */
class MollieOrderLines
{

    const TABLE_NAME = 'mollie_order_lines';

    /**
     * @var array
     */
    protected static $tableData = [
        'title'   => self::TABLE_NAME,
        'columns' => [
            'id'              => [
                'type'   => Table::TYPE_BIGINT,
                'length' => null,
                'option' => ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true]
            ],
            'item_id'         => [
                'type'   => Table::TYPE_BIGINT,
                'length' => null,
                'option' => ['unsigned' => true, 'nullable' => false, 'default' => 0]
            ],
            'line_id'         => [
                'type'   => Table::TYPE_TEXT,
                'length' => 255,
                'option' => ['nullable' => false]
            ],
            'order_id'        => [
                'type'   => Table::TYPE_BIGINT,
                'length' => null,
                'option' => ['unsigned' => true, 'nullable' => false, 'default' => 0]
            ],
            'type'            => [
                'type'   => Table::TYPE_TEXT,
                'length' => 255,
                'option' => ['nullable' => false]
            ],
            'sku'             => [
                'type'   => Table::TYPE_TEXT,
                'length' => 255,
                'option' => ['nullable' => false]
            ],
            'qty_ordered'     => [
                'type'   => Table::TYPE_INTEGER,
                'length' => null,
                'option' => ['unsigned' => true, 'nullable' => false, 'default' => 0]
            ],
            'qty_paid'        => [
                'type'   => Table::TYPE_INTEGER,
                'length' => null,
                'option' => ['unsigned' => true, 'nullable' => false, 'default' => 0]
            ],
            'qty_canceled'    => [
                'type'   => Table::TYPE_INTEGER,
                'length' => null,
                'option' => ['unsigned' => true, 'nullable' => false, 'default' => 0]
            ],
            'qty_shipped'     => [
                'type'   => Table::TYPE_INTEGER,
                'length' => null,
                'option' => ['unsigned' => true, 'nullable' => false, 'default' => 0]
            ],
            'qty_refunded'    => [
                'type'   => Table::TYPE_INTEGER,
                'length' => null,
                'option' => ['unsigned' => true, 'nullable' => false, 'default' => 0]
            ],
            'unit_price'      => [
                'type'   => Table::TYPE_DECIMAL,
                'length' => '12,2',
                'option' => ['nullable' => false]
            ],
            'discount_amount' => [
                'type'   => Table::TYPE_DECIMAL,
                'length' => '12,2',
                'option' => ['nullable' => false]
            ],
            'total_amount'    => [
                'type'   => Table::TYPE_DECIMAL,
                'length' => '12,2',
                'option' => ['nullable' => false]
            ],
            'vat_rate'        => [
                'type'   => Table::TYPE_DECIMAL,
                'length' => '12,2',
                'option' => ['nullable' => false]
            ],
            'vat_amount'      => [
                'type'   => Table::TYPE_DECIMAL,
                'length' => '12,2',
                'option' => ['nullable' => false]
            ],
            'currency'        => [
                'type'   => Table::TYPE_TEXT,
                'length' => 3,
                'option' => ['nullable' => false]
            ],
            'created_at'      => [
                'type'   => Table::TYPE_TIMESTAMP,
                'length' => null,
                'option' => ['nullable' => false, 'default' => Table::TIMESTAMP_INIT]
            ],
            'updated_at'      => [
                'type'   => Table::TYPE_TIMESTAMP,
                'length' => null,
                'option' => ['nullable' => true, 'default' => Table::TIMESTAMP_INIT_UPDATE]
            ],
        ],
        'comment' => 'Mollie Order Lines',
        'indexes' => ['item_id', 'line_id', 'order_id', 'type']
    ];

    /**
     * @return array
     */
    public static function getData()
    {
        return self::$tableData;
    }
}
