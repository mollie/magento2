<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Model\Adminhtml\Source;

use Magento\Framework\Data\OptionSourceInterface;

class CaptureMode implements OptionSourceInterface
{
    public const AUTOMATIC = 'automatic';
    public const MANUAL = 'manual';

    public function toOptionArray(): array
    {
        return [
            [
                'value' => static::AUTOMATIC,
                'label' => __('Automatic'),
            ],
            [
                'value' => static::MANUAL,
                'label' => __('Manual'),
            ],
        ];
    }
}
