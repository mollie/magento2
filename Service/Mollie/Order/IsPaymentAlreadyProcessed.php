<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Service\Mollie\Order;

use Magento\Sales\Api\Data\OrderInterface;

class IsPaymentAlreadyProcessed
{
    public const PAYMENT_PROCESSED = 'mollie_payment_processed';
    public const STATUS_UPDATED = 'mollie_status_updated';

    public function __construct(
        private readonly CanUseManualCapture $canUseManualCapture,
    ) {}

    public function execute(OrderInterface $order): bool
    {
        if ($this->canUseManualCapture->execute($order)) {
            return false;
        }

        $payment = $order->getPayment();

        if ($payment->getIsTransactionClosed()) {
            return true;
        }

        if ($payment->getAdditionalInformation(self::PAYMENT_PROCESSED) === true) {
            return true;
        }

        return $this->wasProcessedBeforeThisFlagExisted($order);
    }

    private function wasProcessedBeforeThisFlagExisted(OrderInterface $order): bool
    {
        return $order->getPayment()->getAdditionalInformation(self::STATUS_UPDATED) === 1
            && $order->getInvoiceCollection()->count() > 0;
    }
}
