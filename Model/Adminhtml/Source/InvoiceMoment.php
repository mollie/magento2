<?php

namespace Mollie\Payment\Model\Adminhtml\Source;

use Magento\Framework\Data\OptionSourceInterface;

class InvoiceMoment implements OptionSourceInterface
{
    const ON_AUTHORIZE = 'authorize';
    const ON_SHIPMENT = 'shipment';

    public function toOptionArray()
    {
        return [
            [
                'value' => static::ON_AUTHORIZE,
                'label' => __('On Authorize'),
            ],
            [
                'value' => static::ON_SHIPMENT,
                'label' => __('On Shipment'),
            ],
        ];
    }
}