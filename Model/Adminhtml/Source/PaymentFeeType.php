<?php
/**
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Model\Adminhtml\Source;

use Magento\Framework\Data\OptionSourceInterface;

class PaymentFeeType implements OptionSourceInterface
{
    const DISABLED = '';
    const PERCENTAGE = 'percentage';
    const FIXED_FEE = 'fixed_fee';
    const FIXED_FEE_AND_PERCENTAGE = 'fixed_fee_and_percentage';

    /**
     * @inheritDoc
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => static::DISABLED,
                'label' => __('No'),
            ],
            [
                'value' => static::PERCENTAGE,
                'label' => __('Percentage'),
            ],
            [
                'value' => static::FIXED_FEE,
                'label' => __('Fixed Fee'),
            ],
            [
                'value' => static::FIXED_FEE_AND_PERCENTAGE,
                'label' => __('Fixed Fee and Percentage'),
            ],
        ];
    }
}
