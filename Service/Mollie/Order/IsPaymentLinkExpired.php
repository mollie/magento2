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
        $methodCode = $this->methodCode->execute($order);
        $this->methodCode->getExpiresAtMethod();
        if (!$this->expires->availableForMethod($methodCode, $order->getStoreId())) {
            return $this->checkWithDefaultDate($order);
        }

        $expiresAt = $this->expires->atDateForMethod($methodCode, $order->getStoreId());

        return $expiresAt < $order->getCreatedAt();
    }

    private function checkWithDefaultDate(OrderInterface $order): bool
    {
        $date = $this->timezone->scopeDate($order->getStoreId());
        $date = $date->add(new \DateInterval('P28D'));

        return $date->format('Y-m-d H:i:s') < $order->getCreatedAt();
    }
}
