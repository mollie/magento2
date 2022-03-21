<?php
/**
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Model\Adminhtml\Source;

use Magento\Framework\Data\OptionSourceInterface;
use Mollie\Payment\Config;
use Mollie\Payment\Service\Mollie\PaymentMethods;

class EnabledMolliePaymentMethod implements OptionSourceInterface
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var PaymentMethods
     */
    private $methods;

    public function __construct(
        Config $config,
        PaymentMethods $methods
    ) {
        $this->methods = $methods;
        $this->config = $config;
    }

    /**
     * @inheritDoc
     */
    public function toOptionArray()
    {
        return array_filter($this->methods->getCodeswithTitle(), function ($method) {
            return $this->config->isMethodActive($method['value']);
        });
    }
}
