<?php

/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Service\Mollie\Order;

use Magento\Sales\Api\Data\OrderInterface;
use Mollie\Api\Resources\Payment;

class CanRegisterCaptureNotification
{
    public function __construct(
        private readonly CanUseManualCapture $canUseManualCapture,
    ) {
    }

    public function execute(OrderInterface $order, Payment $molliePayment): bool
    {
        if (!$this->canUseManualCapture->execute($order)) {
            return true;
        }

        return $molliePayment->isPaid() && $molliePayment->getAmountCaptured() !== 0.0;
    }
}
