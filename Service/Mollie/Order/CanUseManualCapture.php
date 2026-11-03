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
    public const CAPTURE_MODE = 'mollie_capture_mode';

    public function __construct(
        private readonly Config $config,
    ) {
    }

    public function execute(OrderInterface $order): bool
    {
        return $this->getCaptureMode($order) === CaptureMode::MANUAL;
    }

    /**
     * The capture mode is stored on the payment when the Mollie payment is created, and updated by the webhook with
     * the capture mode that Mollie reports. The configuration only applies to orders that have neither, as a change
     * in the configuration must not affect payments that already exist.
     */
    private function getCaptureMode(OrderInterface $order): ?string
    {
        $payment = $order->getPayment();

        return $payment->getAdditionalInformation()[self::CAPTURE_MODE]
            ?? $this->config->captureMode($payment->getMethod(), storeId($order->getStoreId()));
    }
}
