<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Service\Mollie\Order;

use Magento\Sales\Api\Data\OrderInterface;
use Mollie\Payment\Config;

class SupportsPartialCapture
{
    public function __construct(
        private readonly Config $config,
        private readonly LegacyOrderTransactionId $legacyOrderTransactionId,
    ) {}

    public function execute(OrderInterface $order): bool
    {
        if ($this->legacyOrderTransactionId->matches((string) $order->getMollieTransactionId())) {
            return true;
        }

        return $this->config->supportsPartialCapture(
            $order->getPayment()->getMethod(),
            storeId($order->getStoreId()),
        );
    }
}
