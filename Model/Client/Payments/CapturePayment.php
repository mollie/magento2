<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Model\Client\Payments;

use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Sales\Api\Data\InvoiceInterface;
use Magento\Sales\Model\Order;
use Mollie\Payment\Helper\General;
use Mollie\Payment\Service\Mollie\MollieApiClient;

class CapturePayment
{
    public function __construct(
        private MollieApiClient $mollieApiClient,
        private General $mollieHelper,
        private PriceCurrencyInterface $price,
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

        $capture = $mollieApi->paymentCaptures->createForId($mollieTransactionId, $data);
        $payment->setTransactionId($capture->id);

        $order->addCommentToStatusHistory(
            __(
                'Trying to capture %1. Capture ID: %2',
                $this->price->format($captureAmount),
                $capture->id,
            ),
            $status,
        );
    }
}
