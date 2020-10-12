<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 *  See COPYING.txt for license details.
 */

namespace Mollie\Payment\Model\Adminhtml\Source;

use Magento\Framework\Data\OptionSourceInterface;

class SecondChanceEmailDelay implements OptionSourceInterface
{
    public function toOptionArray()
    {
        $output = [];
        $output[] = [
            'label' => __('1 hours'),
            'value' => 1,
        ];

        for ($i = 2; $i <= 8; $i++) {
            $output[] = [
                'label' => __('%1 hours', $i),
                'value' => $i,
            ];
        }

        return $output;
    }
}