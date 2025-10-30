<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Model\Adminhtml\Source;

use Magento\Framework\Data\OptionSourceInterface;

class InvoiceMoment implements OptionSourceInterface
{
    public const ON_AUTHORIZE = 'authorize';
    public const ON_SHIPMENT = 'shipment';

    public function toOptionArray(): array
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
