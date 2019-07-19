<?php

namespace Mollie\Payment\Model\Adminhtml\Source;

use Magento\Framework\Data\OptionSourceInterface;

class InvoiceMoment implements OptionSourceInterface
{
    public function toOptionArray()
    {
        return [
            [
                'value' => 'authorize',
                'label' => __('On Authorize'),
            ],
            [
                'value' => 'shipment',
                'label' => __('On Shipment'),
            ],
        ];
    }
}