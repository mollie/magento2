<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Service\Mollie\Order;

use Magento\Sales\Api\Data\OrderInterface;
use Mollie\Payment\Config;
use Mollie\Payment\Model\Methods\ApplePay;
use Mollie\Payment\Model\Methods\Creditcard;

class CanUseManualCapture
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

    public function execute(OrderInterface $order): bool
    {
        if (!$this->config->useManualCapture((int)$order->getStoreId())) {
            return false;
        }

        $method = $order->getPayment()->getMethod();
        $supportedMethods = [ApplePay::CODE, Creditcard::CODE];
        if (!in_array($method, $supportedMethods)) {
            return false;
        }

        return true;
    }
}
