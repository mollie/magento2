<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Model\Adminhtml\Source;

use Magento\Framework\Data\OptionSourceInterface;
use Mollie\Payment\Service\Mollie\PaymentMethods;

class MolliePaymentMethod implements OptionSourceInterface
{
    /**
     * @var PaymentMethods
     */
    private $methods;

    public function __construct(
        PaymentMethods $methods
    ) {
        $this->methods = $methods;
    }

    /**
     * @inheritDoc
     */
    public function toOptionArray()
    {
        return array_merge(
            [['value' => '', 'label' => __('None')]],
            [['value' => 'first_mollie_method', 'label' => __('First available Mollie method')]],
            $this->methods->getCodesWithTitle()
        );
    }
}
