<?php

/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Service\Mollie\Order;

use DateTime;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Mollie\Payment\Service\Mollie\Order\Transaction\Expires;
use Mollie\Payment\Service\Order\MethodCode;

class IsPaymentLinkExpired
{
    public function __construct(
        private MethodCode $methodCode,
        private Expires $expires,
        private TimezoneInterface $timezone
    ) {}

    public function execute(OrderInterface $order): bool
    {
        $this->methodCode->execute($order);
        $methodCode = $this->methodCode->getExpiresAtMethod();
        $storeId = storeId($order->getStoreId());
        if (!$this->expires->availableForMethod($methodCode, $storeId)) {
            return $this->checkWithDefaultDate($order);
        }

        $expiresAt = $this->expires->atDateForMethod($methodCode, $storeId);

        return $expiresAt < $order->getCreatedAt();
    }

    /**
     * Default for when no expiry date is set on the chosen method.
     */
    private function checkWithDefaultDate(OrderInterface $order): bool
    {
        $storeId = storeId($order->getStoreId());
        $now = $this->timezone->scopeDate($storeId);
        $orderDate = $this->timezone->scopeDate($storeId, new DateTime($order->getCreatedAt()));
        $diff = $now->diff($orderDate);

        return $diff->days >= 28;
    }
}
