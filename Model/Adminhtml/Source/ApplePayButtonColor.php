<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Model\Adminhtml\Source;

use Magento\Framework\Data\OptionSourceInterface;

class ApplePayButtonColor implements OptionSourceInterface
{
    const BLACK = 'black';
    const WHITE = 'white';
    const WHITE_OUTLINE = 'white-outline';

    public function toOptionArray()
    {
        return [
            [
                'value' => static::BLACK,
                'label' => __('Black'),
            ],
            [
                'value' => static::WHITE,
                'label' => __('White'),
            ],
            [
                'value' => static::WHITE_OUTLINE,
                'label' => __('White Outline'),
            ],
        ];
    }
}
