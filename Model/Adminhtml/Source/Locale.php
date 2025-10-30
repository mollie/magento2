<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Model\Adminhtml\Source;

use Magento\Framework\Data\OptionSourceInterface;
use Mollie\Payment\Helper\General as MollieHelper;

/**
 * Class Locale
 *
 * @package Mollie\Payment\Model\Adminhtml\Source
 */
class Locale implements OptionSourceInterface
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
    public function toOptionArray(): array
    {
        if (!$this->options) {
            $this->options = [
                [
                    'value' => '',
                    'label' => __('Autodetect'),
                ],
                [
                    'value' => 'store',
                    'label' => __('Store Locale'),
                ],
            ];
            foreach (MollieHelper::SUPPORTED_LOCAL as $local) {
                $this->options[] = ['value' => $local, 'label' => __($local)];
            }
        }

        return $this->options;
    }
}
