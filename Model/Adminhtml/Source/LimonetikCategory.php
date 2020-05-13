<?php
/**
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Model\Adminhtml\Source;

use Magento\Framework\Data\OptionSourceInterface;

class LimonetikCategory implements OptionSourceInterface
{
    const NULL = 'null';
    const FOOD_AND_DRINKS = 'food_and_drinks';
    const HOME_AND_GARDEN = 'home_and_garden';
    const GIFTS_AND_FLOWERS = 'gifts_and_flowers';
    const CUSTOM_ATTRIBUTE = 'custom_attribute';

    public function toOptionArray()
    {
        return [
            ['value' => static::NULL, 'label' => __('None')],
            ['value' => static::FOOD_AND_DRINKS, 'label' => __('Food and drinks')],
            ['value' => static::HOME_AND_GARDEN, 'label' => __('Home and garden')],
            ['value' => static::GIFTS_AND_FLOWERS, 'label' => __('Gifts and flowers')],
            ['value' => static::CUSTOM_ATTRIBUTE, 'label' => __('Custom attribute')],
        ];
    }
}