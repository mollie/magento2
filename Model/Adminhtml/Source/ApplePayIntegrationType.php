<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Model\Adminhtml\Source;

use Magento\Framework\Data\OptionSourceInterface;

class ApplePayIntegrationType implements OptionSourceInterface
{
    public const EXTERNAL = 'external';
    public const DIRECT = 'direct';

    public function toOptionArray(): array
    {
        return [
            [
                'label' => __('External'),
                'value' => static::EXTERNAL,
            ],
            [
                'label' => __('Direct'),
                'value' => static::DIRECT,
            ],
        ];
    }
}
