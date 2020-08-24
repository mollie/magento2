<?php
/**
 * Copyright Magmodules.eu. All rights reserved.
 *  * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Model\Adminhtml\Source;

use Magento\Framework\Data\OptionSourceInterface;

class WebhookUrlOptions implements OptionSourceInterface
{
    const ENABLED = 'enabled';
    const CUSTOM_URL = 'custom_url';
    const DISABLED = 'disabled';

    public function toOptionArray()
    {
        return [
            [
                'label' => __('Enabled'),
                'value' => static::ENABLED,
            ],
            [
                'label' => __('Custom URL'),
                'value' => static::CUSTOM_URL,
            ],
            [
                'label' => __('Disabled'),
                'value' => static::DISABLED,
            ],
        ];
    }
}