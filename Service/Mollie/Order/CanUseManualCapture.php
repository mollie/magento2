<?php

/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Service\Mollie\Order;

use Magento\Sales\Api\Data\OrderInterface;
use Mollie\Payment\Config;
use Mollie\Payment\Model\Adminhtml\Source\CaptureMode;

class CanUseManualCapture
{
    public function __construct(
        private readonly Config $config,
    ) {
    }

    public function execute(OrderInterface $order): bool
    {
        $method = $order->getPayment()->getMethod();
        if ($this->config->captureMode($method, storeId($order->getStoreId())) != CaptureMode::MANUAL) {
            return false;
        }

        return true;
    }
}
