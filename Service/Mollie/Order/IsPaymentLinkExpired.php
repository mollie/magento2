<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Service\Mollie\Order;

use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Mollie\Payment\Service\Mollie\Order\Transaction\Expires;
use Mollie\Payment\Service\Order\MethodCode;

class IsPaymentLinkExpired
{
    /**
     * @var MethodCode
     */
    private $methodCode;
    /**
     * @var Expires
     */
    private $expires;
    /**
     * @var TimezoneInterface
     */
    private $timezone;

    public function __construct(
        MethodCode $methodCode,
        Expires $expires,
        TimezoneInterface $timezone
    ) {
        $this->methodCode = $methodCode;
        $this->expires = $expires;
        $this->timezone = $timezone;
    }

    public function execute(OrderInterface $order): bool
    {
        $this->methodCode->execute($order);
        $methodCode = $this->methodCode->getExpiresAtMethod();
        if (!$this->expires->availableForMethod($methodCode, $order->getStoreId())) {
            return $this->checkWithDefaultDate($order);
        }

        $expiresAt = $this->expires->atDateForMethod($methodCode, $order->getStoreId());

        return $expiresAt < $order->getCreatedAt();
    }

    /**
     * Default for when no expiry date is set on the chosen method.
     */
    private function checkWithDefaultDate(OrderInterface $order): bool
    {
        $now = $this->timezone->scopeDate($order->getStoreId());
        $orderDate = $this->timezone->scopeDate($order->getStoreId(), new \DateTime($order->getCreatedAt()));
        $diff = $now->diff($orderDate);

        return $diff->days >= 28;
    }
}
