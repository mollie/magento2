<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Model\Adminhtml\Source;

use Magento\Framework\Data\OptionSourceInterface;

class CaptureDelayUnit implements OptionSourceInterface
{
    public const HOURS = 'hours';
    public const DAYS = 'days';

    public function toOptionArray(): array
    {
        return [
            [
                'value' => static::HOURS,
                'label' => __('Hours'),
            ],
            [
                'value' => static::DAYS,
                'label' => __('Days'),
            ],
        ];
    }
}
