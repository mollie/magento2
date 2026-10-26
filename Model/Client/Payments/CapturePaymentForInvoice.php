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
use Mollie\Payment\Model\OrderLines;
use Mollie\Payment\Service\Mollie\MollieApiClient;
use Mollie\Payment\Service\Mollie\Order\CaptureLegacyOrder;
use Mollie\Payment\Service\Mollie\Order\IsSharedTransaction;
use Mollie\Payment\Service\Mollie\Order\LegacyOrderTransactionId;
use Mollie\Payment\Service\Mollie\Order\ValidatePartialCapture;

class CapturePaymentForInvoice
{
    public function __construct(
        private MollieApiClient $mollieApiClient,
        private General $mollieHelper,
        private PriceCurrencyInterface $price,
        private OrderRepositoryInterface $orderRepository,
        private CaptureLegacyOrder $captureLegacyOrder,
        private LegacyOrderTransactionId $legacyOrderTransactionId,
        private OrderLines $orderLines,
        private ValidatePartialCapture $validatePartialCapture,
        private IsSharedTransaction $isSharedTransaction,
    ) {
    }

    public function execute(InvoiceInterface $invoice): void
    {
        $this->validatePartialCapture->execute($invoice);

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
        if (!$this->coversTheCompletePayment($invoice)) {
            $data['amount'] = $this->getAmount($invoice);
        }

        $captureId = $this->capture($mollieApi, $invoice, $mollieTransactionId, $data);
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

    private function coversTheCompletePayment(InvoiceInterface $invoice): bool
    {
        $order = $invoice->getOrder();
        if ($invoice->getBaseGrandTotal() != $order->getBaseGrandTotal()) {
            return false;
        }

        return !$this->isSharedTransaction->execute($order);
    }

    /**
     * Uses the same currency as the payment was created in, see \Mollie\Payment\Helper\General::getOrderAmountByOrder
     *
     * @return array{currency: string, value: string}
     */
    private function getAmount(InvoiceInterface $invoice): array
    {
        $order = $invoice->getOrder();
        if ($this->mollieHelper->useBaseCurrency(storeId($order->getStoreId()))) {
            return $this->mollieHelper->getAmountArray($order->getBaseCurrencyCode(), (float) $invoice->getBaseGrandTotal());
        }

        return $this->mollieHelper->getAmountArray($order->getOrderCurrencyCode(), (float) $invoice->getGrandTotal());
    }

    /**
     * @param array<string, mixed> $data
     */
    private function capture(
        MollieApi $mollieApi,
        InvoiceInterface $invoice,
        string $transactionId,
        array $data,
    ): string {
        if ($this->legacyOrderTransactionId->matches($transactionId)) {
            $payload = isset($data['amount']) ? $this->orderLines->getInvoiceOrderLines($invoice) : [];

            return $this->captureLegacyOrder->execute($mollieApi, $transactionId, $payload);
        }

        return $mollieApi->paymentCaptures->createForId($transactionId, $data)->id;
    }
}
