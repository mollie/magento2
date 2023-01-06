<?php

namespace Mollie\Payment\Model\Adminhtml\Source;

use Magento\Framework\Data\OptionSourceInterface;

class SecondChancePaymentMethod implements OptionSourceInterface
{
    public const USE_PREVIOUS_METHOD = 'use_method_of_original_order';

    /**
     * @var EnabledMolliePaymentMethod
     */
    private $enabledMolliePaymentMethod;

    public function __construct(
        EnabledMolliePaymentMethod $enabledMolliePaymentMethod
    ) {
        $this->enabledMolliePaymentMethod = $enabledMolliePaymentMethod;
    }

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
