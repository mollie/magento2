<?php
/**
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Model\Adminhtml\Source;

use Magento\Framework\Data\OptionSourceInterface;

class VoucherCategory implements OptionSourceInterface
{
    const NULL = 'null';
    const MEAL = 'meal';
    const ECO = 'eco';
    const GIFT = 'gift';
    const CUSTOM_ATTRIBUTE = 'custom_attribute';

    public function toOptionArray()
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