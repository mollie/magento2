<?php

namespace Mollie\Payment\Plugin\Sales\Api;

use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\OrderManagementInterface;
use Mollie\Payment\Service\LockService;

class OrderManagementPlugin
{
    /**
     * @var LockService
     */
    private $lockService;

    public function __construct(
        LockService $lockService
    ) {
        $this->lockService = $lockService;
    }

    public function aroundCancel(
        OrderManagementInterface $subject,
        callable $proceed,
        $orderId
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
