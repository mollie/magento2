<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Service\Order\TransactionPart;

use Magento\Sales\Api\Data\OrderInterface;
use Mollie\Payment\Config;
use Mollie\Payment\Model\Adminhtml\Source\CaptureMode;
use Mollie\Payment\Service\Order\TransactionPartInterface;

class CaptureDelay implements TransactionPartInterface
{
    public function __construct(
        private readonly Config $config,
    ) {}

    public function process(OrderInterface $order, array $transaction): array
    {
        $method = $order->getPayment()->getMethod();
        $storeId = storeId($order->getStoreId());

        if ($this->config->captureMode($method, $storeId) === CaptureMode::MANUAL) {
            return $transaction;
        }

        $delay = (int) $this->config->captureDelay($method, $storeId);
        if ($delay <= 0) {
            return $transaction;
        }

        $unit = $this->config->captureDelayUnit($method, $storeId) ?: 'hours';
        $transaction['captureDelay'] = $delay . ' ' . $unit;

        return $transaction;
    }
}
