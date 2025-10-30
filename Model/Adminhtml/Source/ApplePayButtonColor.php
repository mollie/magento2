<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Model\Adminhtml\Source;

use Magento\Framework\Data\OptionSourceInterface;

class ApplePayButtonColor implements OptionSourceInterface
{
    public const BLACK = 'black';
    public const WHITE = 'white';
    public const WHITE_OUTLINE = 'white-outline';

    public function toOptionArray(): array
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
