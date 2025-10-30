<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Service\Order;

use Magento\Sales\Api\Data\CreditmemoInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Mollie\Api\MollieApiClient;
use Mollie\Payment\Service\Mollie\Order\RefundUsingPayment;

class ProcessAdjustmentFee
{
    private bool $doNotRefundInMollie = false;

    public function __construct(
        private RefundUsingPayment $refundUsingPayment
    ) {}

    public function handle(MollieApiClient $mollieApi, OrderInterface $order, CreditmemoInterface $creditmemo): void
    {
        if ($creditmemo->getAdjustment() > 0) {
            $this->positive($mollieApi, $order, $creditmemo);
        }

        if ($creditmemo->getAdjustmentNegative() != 0) {
            $this->negative($mollieApi, $order, $creditmemo);
        }
    }

    public function doNotRefundInMollie(): bool
    {
        return $this->doNotRefundInMollie;
    }

    private function positive(MollieApiClient $mollieApi, OrderInterface $order, CreditmemoInterface $creditmemo): void
    {
        $this->doNotRefundInMollie = false;

        $this->refundUsingPayment->execute(
            $mollieApi,
            $order->getMollieTransactionId(),
            $order->getOrderCurrencyCode(),
            $creditmemo->getAdjustment(),
        );
    }

    private function negative(MollieApiClient $mollieApi, OrderInterface $order, CreditmemoInterface $creditmemo): void
    {
        $this->doNotRefundInMollie = true;

        $this->refundUsingPayment->execute(
            $mollieApi,
            $order->getMollieTransactionId(),
            $order->getOrderCurrencyCode(),
            $creditmemo->getGrandTotal(),
        );
    }
}
