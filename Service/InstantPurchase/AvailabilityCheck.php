<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Service\InstantPurchase;

use Magento\InstantPurchase\PaymentMethodIntegration\AvailabilityCheckerInterface;
use Mollie\Payment\Config;

class AvailabilityCheck implements AvailabilityCheckerInterface
{
    public function __construct(
        private Config $config
    ) {}

    public function isAvailable(): bool
    {
        return $this->config->isMagentoVaultEnabled();
    }
}
