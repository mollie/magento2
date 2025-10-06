<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Block\Adminhtml\Widget\Grid;

use Magento\Backend\Block\Widget\Grid\Column;
use Magento\Sales\Api\Data\TransactionInterface;
use Magento\Sales\Model\ResourceModel\Transaction\Grid\Collection;

class PaypalReferenceColumn extends Column
{
    public function _construct()
    {
        parent::_construct();

        $this->setData(
            'filter_condition_callback',
            [$this, 'filterPaypalReference']
        );
    }

    public function getFrameCallback(): array
    {
        return [$this, 'decorate'];
    }

    public function decorate($value, TransactionInterface $row): string
    {
        $information = $row->getData('additional_information');
        if (!array_key_exists('details', $information)) {
            return '';
        }

        $details = json_decode($information['details'], true);
        if (!array_key_exists('paypalReference', $details)) {
            return '';
        }

        return $details['paypalReference'];
    }

    public function filterPaypalReference(Collection $collection, Column $column)
    {
        if (!$this->getFilter()->getValue()) {
            return;
        }

        $value = $this->getFilter()->getValue();
        $collection->addFieldToFilter('sop.additional_information', ['like' => '%' . $value . '%']);
    }
}
