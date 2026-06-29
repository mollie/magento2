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
use Mollie\Api\Http\Requests\DynamicPostRequest;
use Mollie\Payment\Helper\General;
use Mollie\Payment\Service\Mollie\MollieApiClient;

class CapturePaymentForInvoice
{
    private const ORDERS_API_PREFIX = 'ord_';

    public function __construct(
        private MollieApiClient $mollieApiClient,
        private General $mollieHelper,
        private PriceCurrencyInterface $price,
        private OrderRepositoryInterface $orderRepository,
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

        // Orders placed on the module's V2 stored an Orders API id (ord_...). Those payments are linked to an
        // order and cannot be captured through the Payments API (422 "use the Shipments API instead"). The
        // Orders API captures them by creating a shipment, so ship all lines to capture the full authorised
        // amount, matching the pre-V3 behaviour.
        if (str_starts_with((string) $mollieTransactionId, self::ORDERS_API_PREFIX)) {
            $shipment = $mollieApi->send(
                new DynamicPostRequest('orders/' . $mollieTransactionId . '/shipments'),
            )->toArray();
            $captureId = $shipment['id'] ?? $mollieTransactionId;
        } else {
            $captureId = $mollieApi->paymentCaptures->createForId($mollieTransactionId, $data)->id;
        }

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
}
