<?php

declare(strict_types=1);

namespace Mollie\Payment\Service\Order\Invoice;

use Mollie\Payment\Config;

class ShouldEmailInvoice
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

    public function execute(int $storeId, string $paymentMethod): bool
    {
        if (!$this->config->sendInvoiceEmail($storeId)) {
            return false;
        }

        if (!$this->isKlarna($paymentMethod)) {
            return true;
        }

        return $this->config->sendInvoiceEmailForKlarna($storeId);
    }

    private function isKlarna(string $paymentMethod)
    {
        return in_array(
            $paymentMethod,
            [
                'mollie_methods_klarna',
                'mollie_methods_klarnapaylater',
                'mollie_methods_klarnapaynow',
                'mollie_methods_klarnasliceit',
            ]
        );
    }
}
