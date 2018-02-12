<?php
/**
 * Copyright © 2018 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Model\Adminhtml\Source;

use Magento\Framework\Option\ArrayInterface;

/**
 * Class Locale
 *
 * @package Mollie\Payment\Model\Adminhtml\Source
 */
class Locale implements ArrayInterface
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
                    'value' => '',
                    'label' => __('Autodetect')
                ],
                [
                    'value' => 'store',
                    'label' => __('Store Locale')
                ],
                [
                    'value' => 'en_US',
                    'label' => __('en_US')
                ],
                [
                    'value' => 'de_AT',
                    'label' => __('de_AT')
                ],
                [
                    'value' => 'de_CH',
                    'label' => __('de_CH')
                ],
                [
                    'value' => 'de_DE',
                    'label' => __('de_DE')
                ],
                [
                    'value' => 'es_ES',
                    'label' => __('es_ES')
                ],
                [
                    'value' => 'fr_BE',
                    'label' => __('fr_BE')
                ],
                [
                    'value' => 'nl_BE',
                    'label' => __('nl_BE')
                ],
                [
                    'value' => 'nl_NL',
                    'label' => __('nl_NL')
                ],
            ];
        }
        return $this->options;
    }
}
