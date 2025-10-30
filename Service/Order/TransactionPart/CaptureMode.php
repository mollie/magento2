<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Service\Order\TransactionPart;

use Magento\Sales\Api\Data\OrderInterface;
use Mollie\Payment\Service\Mollie\Order\CanUseManualCapture;
use Mollie\Payment\Service\Order\TransactionPartInterface;

class CaptureMode implements TransactionPartInterface
{
    public function __construct(
        private CanUseManualCapture $canUseManualCapture,
    ) {
    }

    public function process(OrderInterface $order, array $transaction): array
    {
        if (!$this->canUseManualCapture->execute($order)) {
            return $transaction;
        }

        $transaction['captureMode'] = 'manual';

        return $transaction;
    }
}
