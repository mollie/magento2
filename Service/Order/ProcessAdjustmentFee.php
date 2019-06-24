<?php

namespace Mollie\Payment\Service\Order;

use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\CreditmemoInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Mollie\Api\MollieApiClient;
use Mollie\Api\Resources\PaymentFactory;
use Mollie\Payment\Helper\General as MollieHelper;

class ProcessAdjustmentFee
{
    /**
     * @var MollieHelper
     */
    private $mollieHelper;

    /**
     * @var PaymentFactory
     */
    private $paymentFactory;

    /**
     * @var bool
     */
    private $doNotRefundInMollie = false;

    public function __construct(
        MollieHelper $mollieHelper,
        PaymentFactory $paymentFactory
    ) {
        $this->mollieHelper = $mollieHelper;
        $this->paymentFactory = $paymentFactory;
    }

    public function handle(MollieApiClient $mollieApi, OrderInterface $order, CreditmemoInterface $creditmemo)
    {
        if ($creditmemo->getAdjustment() > 0) {
            $this->positive($mollieApi, $order, $creditmemo);
        }

        if ($creditmemo->getAdjustmentNegative() != 0) {
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

        $this->paymentRefund($mollieApi, $order, $creditmemo->getAdjustment());
    }

    private function negative(MollieApiClient $mollieApi, OrderInterface $order, CreditmemoInterface $creditmemo)
    {
        $this->doNotRefundInMollie = true;

        $amountToRefund = $order->getGrandTotal() - abs($creditmemo->getAdjustmentNegative());

        $this->paymentRefund($mollieApi, $order, $amountToRefund);
    }

    private function paymentRefund(MollieApiClient $mollieApi, OrderInterface $order, $amount)
    {
        $mollieOrder = $mollieApi->orders->get($order->getMollieTransactionId(), ['embed' => 'payments']);
        $payments = $mollieOrder->_embedded->payments;

        try {
            $payment = $this->paymentFactory->create([$mollieApi]);
            $payment->id = current($payments)->id;

            $mollieApi->payments->refund($payment, [
                'amount' => [
                    'currency' => $order->getOrderCurrencyCode(),
                    'value' => $this->mollieHelper->formatCurrencyValue(
                        $amount,
                        $order->getOrderCurrencyCode()
                    ),
                ]
            ]);
        } catch (\Exception $exception) {
            $this->mollieHelper->addTolog('error', $exception->getMessage());
            throw new LocalizedException(
                __('Mollie API: %1', $exception->getMessage())
            );
        }
    }
}