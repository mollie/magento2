<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Model\Adminhtml\Source;

use Magento\Framework\Data\OptionSourceInterface;

class SecondChancePaymentMethod implements OptionSourceInterface
{
    public const USE_PREVIOUS_METHOD = 'use_method_of_original_order';

    public function __construct(
        private EnabledMolliePaymentMethod $enabledMolliePaymentMethod
    ) {}

    public function toOptionArray(): array
    {
        $options = $this->enabledMolliePaymentMethod->toOptionArray();

        array_unshift($options, [
            'value' => static::USE_PREVIOUS_METHOD,
            'label' => __('Use the method of the original order'),
        ]);

        return $options;
    }
}
