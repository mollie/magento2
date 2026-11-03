<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Service\Mollie;

use Mollie\Payment\Config;
use Mollie\Payment\Model\Methods\Expresscomponents;

class ExpressComponentsAvailability
{
    public function __construct(
        private readonly Config $config
    ) {
    }

    public function isAvailable(): bool
    {
        return $this->config->isModuleEnabled() &&
            $this->config->isMethodActive(Expresscomponents::CODE);
    }
}
