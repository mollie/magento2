<?php
/**
 *  Copyright © 2018 Magmodules.eu. All rights reserved.
 *  See COPYING.txt for license details.
 */

namespace Mollie\Payment\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\Model\AbstractModel;

/**
 * Class OrderLines
 *
 * @package Mollie\Payment\Model\ResourceModel
 */
class OrderLines extends AbstractDb
{

    /**
     *
     */
    public function _construct()
    {
        $this->_init('mollie_order_lines', 'id');
    }

    /**
     * @param AbstractModel $object
     *
     * @return AbstractDb
     */
    protected function _beforeSave(AbstractModel $object)
    {
        if ($qty = $object->getData('quantity')) {
            $object->setData('qty_ordered', $qty);
        }

        if ($unitPrice = $object->getData('unitPrice')) {
            $unitPriceValue = isset($unitPrice['value']) ? $unitPrice['value'] : '';
            $object->setData('unit_price', $unitPriceValue);
        }

        if ($discountAmount = $object->getData('discountAmount')) {
            $discountAmountValue = isset($discountAmount['value']) ? $discountAmount['value'] : '';
            $object->setData('discount_amount', $discountAmountValue);
        }

        if ($totalAmount = $object->getData('totalAmount')) {
            $totalAmountValue = isset($totalAmount['value']) ? $totalAmount['value'] : '';
            $totalAmountCurrency = isset($totalAmount['currency']) ? $totalAmount['currency'] : '';
            $object->setData('total_amount', $totalAmountValue);
            $object->setData('currency', $totalAmountCurrency);
        }

        if ($vatRate = $object->getData('vatRate')) {
            $object->setData('vat_rate', $vatRate);
        }

        if ($vatAmount = $object->getData('vatAmount')) {
            $vatAmountValue = isset($vatAmount['value']) ? $vatAmount['value'] : '';
            $object->setData('vat_amount', $vatAmountValue);
        }

        return parent::_beforeSave($object);
    }
}
