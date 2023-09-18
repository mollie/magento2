<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Service\Mollie\Order;

use Magento\Sales\Api\Data\OrderInterface;
use Mollie\Api\Resources\Payment;
use Mollie\Payment\Config;
use Mollie\Payment\Model\Methods\Creditcard;

class CanRegisterCaptureNotification
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

    public function execute(OrderInterface $order, Payment $molliePayment): bool
    {
        if (!$this->config->useManualCapture($order->getStoreId()) ||
            $order->getPayment()->getMethod() != Creditcard::CODE
        ) {
            return true;
        }

        return $molliePayment->isPaid() && $molliePayment->getAmountCaptured() !== 0.0;
    }
}
