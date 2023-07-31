<?php

declare(strict_types=1);

namespace Mollie\Payment\Test\Fakes\Service;

use Magento\Sales\Api\Data\OrderInterface;
use Mollie\Payment\Service\OrderLockService;

class OrderLockServiceFake extends OrderLockService
{
    public function execute(OrderInterface $order, callable $callback)
    {
        return $callback($order);
    }
}
