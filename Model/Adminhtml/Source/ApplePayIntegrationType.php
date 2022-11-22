<?php
/**
 * Copyright Magmodules.eu. All rights reserved.
 *  * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Model\Adminhtml\Source;

use Magento\Framework\Data\OptionSourceInterface;

class ApplePayIntegrationType implements OptionSourceInterface
{
    const EXTERNAL = 'external';
    const DIRECT = 'direct';

    public function toOptionArray()
    {
        return [
            [
                'label' => __('External'),
                'value' => static::EXTERNAL,
            ],
            [
                'label' => __('Direct'),
                'value' => static::DIRECT,
            ],
        ];
    }
}
