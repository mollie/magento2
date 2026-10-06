<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Model\Adminhtml\Source;

use Magento\Framework\Data\OptionSourceInterface;

class PaymentInstructionsStyle implements OptionSourceInterface
{
    public const NONE = 'none';
    public const PLAIN = 'plain';
    public const INFO = 'info';
    public const WARNING = 'warning';
    public const SUCCESS = 'success';

    public function toOptionArray(): array
    {
        return [
            [
                'value' => static::NONE,
                'label' => __('No instructions'),
            ],
            [
                'value' => static::INFO,
                'label' => __('Information'),
            ],
            [
                'value' => static::WARNING,
                'label' => __('Warning'),
            ],
            [
                'value' => static::SUCCESS,
                'label' => __('Success'),
            ],
            [
                'value' => static::PLAIN,
                'label' => __('Plain text'),
            ],
        ];
    }
}
