<?php

/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Service\Mollie\ApplePay;

use Mollie\Payment\Config;
use Mollie\Payment\Model\Adminhtml\Source\CaptureMode;
use Mollie\Payment\Model\Methods\Creditcard;

class SupportedNetworks
{
    public function __construct(
        private Config $config
    ) {}

    public function execute(?int $storeId = null): array
    {
        $output = ['amex', 'masterCard', 'visa'];
        if ($this->config->captureMode(Creditcard::CODE, $storeId) == CaptureMode::AUTOMATIC) {
            $output[] = 'maestro';
            $output[] = 'vPay';
        }

        return $output;
    }
}
