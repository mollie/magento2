<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Model\Adminhtml\Source;

use Magento\Framework\Data\OptionSourceInterface;
use Mollie\Payment\Config;
use Mollie\Payment\Service\Mollie\PaymentMethods;

class EnabledMolliePaymentMethod implements OptionSourceInterface
{
    public function __construct(
        private Config $config,
        private PaymentMethods $methods
    ) {}

    /**
     * @inheritDoc
     */
    public function toOptionArray(): array
    {
        return array_filter($this->methods->getCodeswithTitle(), function (array $method): bool {
            return $this->config->isMethodActive($method['value']);
        });
    }
}
