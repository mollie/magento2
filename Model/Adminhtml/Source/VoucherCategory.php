<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Model\Adminhtml\Source;

use Magento\Framework\Data\OptionSourceInterface;

class VoucherCategory implements OptionSourceInterface
{
    public const NULL = 'null';
    public const MEAL = 'meal';
    public const ECO = 'eco';
    public const GIFT = 'gift';
    public const CUSTOM_ATTRIBUTE = 'custom_attribute';

    public function toOptionArray(): array
    {
        return [
            ['value' => static::NULL, 'label' => __('None')],
            ['value' => static::MEAL, 'label' => __('Meal')],
            ['value' => static::ECO, 'label' => __('Eco')],
            ['value' => static::GIFT, 'label' => __('Gift')],
            ['value' => static::CUSTOM_ATTRIBUTE, 'label' => __('Custom attribute')],
        ];
    }
}
