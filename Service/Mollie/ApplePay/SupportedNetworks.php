<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Service\Mollie\ApplePay;

use Mollie\Payment\Config;

class SupportedNetworks
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

    public function execute(?int $storeId = null): array
    {
        $output = ['amex', 'masterCard', 'visa'];
        if (!$this->config->useManualCapture($storeId)) {
            $output[] = 'maestro';
            $output[] = 'vPay';
        }

        return $output;
    }
}
