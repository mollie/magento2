<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Service\Mollie\Order;

use Mollie\Payment\Config;
use Mollie\Payment\Model\Adminhtml\Source\CaptureMoment;

class WhenToCapture
{
    public function __construct(
        private readonly Config $config,
    ) {}

    public function onInvoice(string $method, ?int $storeId = null): bool
    {
        return $this->config->whenToCapture($method, $storeId) == CaptureMoment::ON_INVOICE;
    }

    public function onShipment(string $method, ?int $storeId = null): bool
    {
        return $this->config->whenToCapture($method, $storeId) == CaptureMoment::ON_SHIPMENT;
    }
}
