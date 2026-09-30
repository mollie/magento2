<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Model\Client\Payments\Processors;

use IntlDateFormatter;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Mollie\Api\Resources\Payment;
use Mollie\Payment\Model\Client\PaymentProcessorInterface;
use Mollie\Payment\Model\Client\ProcessTransactionResponse;
use Mollie\Payment\Model\Client\ProcessTransactionResponseFactory;
use Mollie\Payment\Model\Methods\Paymentlink;
use Mollie\Payment\Service\Mollie\Order\IsPaymentLinkExpired;
use Mollie\Payment\Service\Order\ExpiredOrderToTransaction;
use Mollie\Payment\Service\Order\OrderCommentHistory;

class ExpiredStatusProcessor implements PaymentProcessorInterface
{
    public function __construct(
        private ExpiredOrderToTransaction $expiredOrderToTransaction,
        private FailedStatusProcessor $failedStatusProcessor,
        private ProcessTransactionResponseFactory $processTransactionResponseFactory,
        private IsPaymentLinkExpired $isPaymentLinkExpired,
        private OrderCommentHistory $orderCommentHistory,
        private TimezoneInterface $timezone,
    ) {}

    public function process(
        OrderInterface $magentoOrder,
        Payment $molliePayment,
        string $type,
        ProcessTransactionResponse $response,
    ): ?ProcessTransactionResponse {
        if ($this->hasValidPaymentLink($magentoOrder)) {
            $this->skipExpiredPayment($magentoOrder);

            return $this->createUnsuccessfulResponse($magentoOrder, $molliePayment, $type);
        }

        if ($this->shouldCancelProcessing($magentoOrder)) {
            return $this->createUnsuccessfulResponse($magentoOrder, $molliePayment, $type);
        }

        return $this->failedStatusProcessor->process($magentoOrder, $molliePayment, $type, $response);
    }

    private function hasValidPaymentLink(OrderInterface $order): bool
    {
        return $order->getPayment()->getMethod() === Paymentlink::CODE &&
            !$this->isPaymentLinkExpired->execute($order);
    }

    private function skipExpiredPayment(OrderInterface $order): void
    {
        $transactionId = (string) $order->getMollieTransactionId();
        if ($this->expiredOrderToTransaction->getByTransactionId($transactionId)->getSkipped()) {
            return;
        }

        $this->expiredOrderToTransaction->markTransactionAsSkipped($transactionId);

        if (!in_array($order->getState(), Paymentlink::OPEN_ORDER_STATES, true)) {
            return;
        }

        $this->orderCommentHistory->add(
            $order,
            __(
                'The Mollie payment %1 expired. The order stays open because the payment link is valid until %2.',
                $transactionId,
                $this->getFormattedExpiryDate($order),
            ),
        );
    }

    private function getFormattedExpiryDate(OrderInterface $order): string
    {
        return $this->timezone->formatDateTime(
            $this->isPaymentLinkExpired->getExpiresAt($order),
            IntlDateFormatter::MEDIUM,
            IntlDateFormatter::SHORT,
        );
    }

    private function shouldCancelProcessing(OrderInterface $order): bool
    {
        if (!$this->expiredOrderToTransaction->hasMultipleTransactions($order)) {
            return false;
        }

        $this->expiredOrderToTransaction->markTransactionAsSkipped($order->getMollieTransactionId());

        return true;
    }

    private function createUnsuccessfulResponse(
        OrderInterface $order,
        Payment $molliePayment,
        string $type,
    ): ProcessTransactionResponse {
        return $this->processTransactionResponseFactory->create([
            'success' => false,
            'status' => $molliePayment->status,
            'order_id' => $order->getEntityId(),
            'type' => $type,
        ]);
    }
}
