<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Service\InstantPurchase;

use Magento\InstantPurchase\PaymentMethodIntegration\AvailabilityCheckerInterface;
use Mollie\Payment\Config;

class AvailabilityCheck implements AvailabilityCheckerInterface
{
    /**
     * @var Config
     */
    private $config;

    public function __construct(
        Config $config
    ) {
        $this->config = $config;
    }

    public function isAvailable(): bool
    {
        return $this->config->isMagentoVaultEnabled();
    }
}
