<?php

/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Service\Order\Invoice;

use Mollie\Payment\Config;

class ShouldEmailInvoice
{
    public function __construct(
        private Config $config,
    ) {
    }

    public function execute(?int $storeId, string $paymentMethod): bool
    {
        if (!$this->config->sendInvoiceEmail($storeId)) {
            return false;
        }

        if (!$this->isKlarna($paymentMethod)) {
            return true;
        }

        return $this->config->sendInvoiceEmailForKlarna($storeId);
    }

    private function isKlarna(string $paymentMethod): bool
    {
        return $paymentMethod === 'mollie_methods_klarna';
    }
}
