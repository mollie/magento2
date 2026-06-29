<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Model\Client\Payments;

use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Sales\Api\Data\InvoiceInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Mollie\Api\MollieApiClient as MollieApi;
use Mollie\Payment\Helper\General;
use Mollie\Payment\Service\Mollie\MollieApiClient;
use Mollie\Payment\Service\Mollie\Order\CaptureLegacyOrder;
use Mollie\Payment\Service\Mollie\Order\LegacyOrderTransactionId;

class CapturePaymentForInvoice
{
    public function __construct(
        private MollieApiClient $mollieApiClient,
        private General $mollieHelper,
        private PriceCurrencyInterface $price,
        private OrderRepositoryInterface $orderRepository,
        private CaptureLegacyOrder $captureLegacyOrder,
        private LegacyOrderTransactionId $legacyOrderTransactionId,
    ) {
    }

    public function execute(InvoiceInterface $invoice): void
    {
        $order = $invoice->getOrder();
        $payment = $order->getPayment();

        $order->setState(Order::STATE_PAYMENT_REVIEW);
        $status = $order->getConfig()->getStateDefaultStatus(Order::STATE_PAYMENT_REVIEW);

        $captureAmount = $invoice->getBaseGrandTotal();

        $mollieTransactionId = $order->getMollieTransactionId();
        $mollieApi = $this->mollieApiClient->loadByStore(storeId($order->getStoreId()));

        $data = [
            'description' => __('Capture for order %1', $order->getIncrementId()),
        ];
        if ($captureAmount != $order->getBaseGrandTotal()) {
            $data['amount'] = $this->mollieHelper->getAmountArray(
                $order->getOrderCurrencyCode(),
                $captureAmount,
            );
        }

        $captureId = $this->capture($mollieApi, $mollieTransactionId, $data);
        $payment->setTransactionId($mollieTransactionId);

        $order->addCommentToStatusHistory(
            __(
                'Trying to capture %1. Capture ID: %2',
                $this->price->format($captureAmount),
                $captureId,
            ),
            $status,
        );

        $this->orderRepository->save($order);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function capture(MollieApi $mollieApi, string $transactionId, array $data): string
    {
        if ($this->legacyOrderTransactionId->matches($transactionId)) {
            return $this->captureLegacyOrder->execute($mollieApi, $transactionId);
        }

        return $mollieApi->paymentCaptures->createForId($transactionId, $data)->id;
    }
}
