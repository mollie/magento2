<?php
/**
 * Copyright © 2018 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Model\Adminhtml\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Class IssuerListType
 *
 * @package Mollie\Payment\Model\Adminhtml\Source
 */
class IssuerListType implements OptionSourceInterface
{

    /**
     * Options array
     *
     * @var array
     */
    public $options = null;

    /**
     * @return array
     */
    public function toOptionArray()
    {
        if (!$this->options) {
            $this->options = [
                [
                    'value' => 'dropdown',
                    'label' => __('Dropdown')
                ],
                [
                    'value' => 'radio',
                    'label' => __('List with images')
                ],
                [
                    'value' => 'none',
                    'label' => __('Don\'t show issuer list')
                ]

            ];
        }
        return $this->options;
    }
}
