<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Service\Mollie;

use Magento\Payment\Helper\Data;
use Mollie\Payment\Model\Methods\Expresscomponents;

class ExpressComponentsAvailability
{
    public function __construct(
        private readonly Data $paymentHelper
    ) {
    }

    public function isAvailable(): bool
    {
        try {
            return $this->paymentHelper->getMethodInstance(Expresscomponents::CODE)->isAvailable();
        } catch (\Throwable $exception) {
            return false;
        }
    }
}
