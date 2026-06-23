<?php

/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Service\Mollie\Order;

use Magento\Framework\Intl\DateTimeFactory;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Mollie\Payment\Service\Mollie\Order\Transaction\Expires;
use Mollie\Payment\Service\Order\MethodCode;

class IsPaymentLinkExpired
{
    public function __construct(
        private MethodCode $methodCode,
        private Expires $expires,
        private TimezoneInterface $timezone,
        private DateTimeFactory $dateTimeFactory
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
        $orderDate = $this->timezone->scopeDate($storeId, $this->dateTimeFactory->create($order->getCreatedAt()))->format('Y-m-d');

        return $expiresAt < $orderDate;
    }

    /**
     * Default for when no expiry date is set on the chosen method.
     */
    private function checkWithDefaultDate(OrderInterface $order): bool
    {
        $now = $this->dateTimeFactory->create();
        $orderDate = $this->dateTimeFactory->create($order->getCreatedAt());
        $diff = $now->diff($orderDate);

        return $diff->days >= 28;
    }
}
