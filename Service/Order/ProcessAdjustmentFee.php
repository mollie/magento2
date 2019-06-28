<?php

namespace Mollie\Payment\Service\Order;

use Magento\Sales\Api\Data\CreditmemoInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Mollie\Api\MollieApiClient;
use Mollie\Payment\Helper\General as MollieHelper;
use Mollie\Payment\Service\Mollie\Order\RefundUsingPayment;

class ProcessAdjustmentFee
{
    /**
     * @var MollieHelper
     */
    private $mollieHelper;

    /**
     * @var RefundUsingPayment
     */
    private $refundUsingPayment;

    /**
     * @var bool
     */
    private $doNotRefundInMollie = false;

    public function __construct(
        MollieHelper $mollieHelper,
        RefundUsingPayment $refundUsingPayment
    ) {
        $this->mollieHelper = $mollieHelper;
        $this->refundUsingPayment = $refundUsingPayment;
    }

    public function handle(MollieApiClient $mollieApi, OrderInterface $order, CreditmemoInterface $creditmemo)
    {
        if ($creditmemo->getAdjustment() > 0) {
            $this->positive($mollieApi, $order, $creditmemo);
        }

        if ($creditmemo->getAdjustmentNegative() < 0) {
            $this->negative($mollieApi, $order, $creditmemo);
        }
    }

    public function doNotRefundInMollie()
    {
        return $this->doNotRefundInMollie;
    }

    private function positive(MollieApiClient $mollieApi, OrderInterface $order, CreditmemoInterface $creditmemo)
    {
        $this->doNotRefundInMollie = false;

        $this->refundUsingPayment->execute(
            $mollieApi,
            $order->getMollieTransactionId(),
            $order->getOrderCurrencyCode(),
            $creditmemo->getAdjustment()
        );
    }

    private function negative(MollieApiClient $mollieApi, OrderInterface $order, CreditmemoInterface $creditmemo)
    {
        $this->doNotRefundInMollie = true;

        $amountToRefund = $creditmemo->getSubtotal() - $creditmemo->getAdjustmentNegative();

        $this->refundUsingPayment->execute(
            $mollieApi,
            $order->getMollieTransactionId(),
            $order->getOrderCurrencyCode(),
            $amountToRefund
        );
    }
}