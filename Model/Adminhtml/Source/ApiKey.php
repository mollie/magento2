<?php
/**
 * Copyright © 2018 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Model\Adminhtml\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Class ApiKey
 *
 * @package Mollie\Payment\Model\Adminhtml\Source
 */
class ApiKey implements OptionSourceInterface
{

    /**
     * Options array
     *
     * @var array
     */
    public $options = null;

    /**
     * Live/Test Key Array
     *
     * @return array
     */
    public function toOptionArray()
    {
        if (!$this->options) {
            $this->options = [
                ['value' => 'live', 'label' => __('Live')],
                ['value' => 'test', 'label' => __('Test')]
            ];
        }
        return $this->options;
    }
}
