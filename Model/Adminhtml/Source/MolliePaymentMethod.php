<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Model\Adminhtml\Source;

use Magento\Framework\Data\OptionSourceInterface;
use Mollie\Payment\Service\Mollie\PaymentMethods;

class MolliePaymentMethod implements OptionSourceInterface
{
    public function __construct(
        private PaymentMethods $methods
    ) {}

    /**
     * @inheritDoc
     */
    public function toOptionArray(): array
    {
        return array_merge(
            [['value' => '', 'label' => __('None')]],
            [['value' => 'first_mollie_method', 'label' => __('First available Mollie method')]],
            $this->methods->getCodesWithTitle(),
        );
    }
}
