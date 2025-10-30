<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Plugin\Sales\Api;

use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\OrderManagementInterface;
use Mollie\Payment\Service\LockService;

class OrderManagementPlugin
{
    public function __construct(
        private LockService $lockService
    ) {}

    public function aroundCancel(
        OrderManagementInterface $subject,
        callable $proceed,
        string $orderId,
    ) {
        // Lock the order, so we are sure that there are no other operations running on the order at the same time.
        $key = 'mollie.order.' . $orderId;
        if (!$this->lockService->lock($key)) {
            throw new LocalizedException(__('Unable to get lock for %1', $key));
        }

        $result = $proceed($orderId);

        $this->lockService->unlock($key);

        return $result;
    }
}
