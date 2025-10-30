<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Model\Adminhtml\Source;

use Magento\Framework\Data\OptionSourceInterface;

class WebhookUrlOptions implements OptionSourceInterface
{
    public const ENABLED = 'enabled';
    public const CUSTOM_URL = 'custom_url';
    public const DISABLED = 'disabled';

    public function toOptionArray(): array
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
