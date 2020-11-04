<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Ui\DataProvider\PaymentReminder\Listing\Sent;

use Magento\Framework\View\Element\UiComponent\DataProvider\SearchResult;

class Collection extends SearchResult
{
    protected function _initSelect()
    {
        $this->addFilterToMap('entity_id', 'main_table.entity_id');
        parent::_initSelect();

        $this->getSelect()->joinLeft(
            [
                'sales_order' => $this->getTable('sales_order')
            ],
            'main_table.order_id = sales_order.entity_id',
            [
                'increment_id',
                'customer_firstname',
                'customer_lastname',
                'customer_email'
            ]
        );
    }
}