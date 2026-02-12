<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Model\Adminhtml\Source;

use Magento\Framework\Data\OptionSourceInterface;

class CaptureMoment implements OptionSourceInterface
{
    public const ON_INVOICE = 'invoice';
    public const ON_SHIPMENT = 'shipment';

    public function toOptionArray(): array
    {
        return [
            [
                'value' => static::ON_INVOICE,
                'label' => __('On invoice'),
            ],
            [
                'value' => static::ON_SHIPMENT,
                'label' => __('On shipment'),
            ],
        ];
    }
}
