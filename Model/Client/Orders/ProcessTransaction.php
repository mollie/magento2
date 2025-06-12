<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Mollie\Payment\Model\Client\Orders;

use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\OrderInterface;
use Mollie\Payment\Helper\General as MollieHelper;
use Mollie\Payment\Model\Client\Orders\OrderProcessors;
use Mollie\Payment\Model\Client\ProcessTransactionResponse;
use Mollie\Payment\Model\Client\ProcessTransactionResponseFactory;
use Mollie\Payment\Model\OrderLines;
use Mollie\Payment\Service\Mollie\MollieApiClient;
use Mollie\Payment\Service\Mollie\Order\GetTransactionId;
use Mollie\Payment\Service\Mollie\ValidateMetadata;

class ProcessTransaction
{
    /**
     * @var ProcessTransactionResponseFactory
     */
    private $processTransactionResponseFactory;

    /**
     * @var OrderProcessors
     */
    private $orderProcessors;

    /**
     * @var MollieApiClient
     */
    private $mollieApiClient;

    /**
     * @var MollieHelper
     */
    private $mollieHelper;

    /**
     * @var OrderLines
     */
    private $orderLines;

    /**
     * @var ValidateMetadata
     */
    private $validateMetadata;
    /**
     * @var GetTransactionId
     */
    private $getTransactionId;

    public function __construct(
        ProcessTransactionResponseFactory $processTransactionResponseFactory,
        OrderProcessors $orderProcessors,
        MollieApiClient $mollieApiClient,
        MollieHelper $mollieHelper,
        OrderLines $orderLines,
        ValidateMetadata $validateMetadata,
        GetTransactionId $getTransactionId
    ) {
        $this->processTransactionResponseFactory = $processTransactionResponseFactory;
        $this->mollieApiClient = $mollieApiClient;
        $this->mollieHelper = $mollieHelper;
        $this->orderLines = $orderLines;
        $this->orderProcessors = $orderProcessors;
        $this->validateMetadata = $validateMetadata;
        $this->getTransactionId = $getTransactionId;
    }

    public function execute(
        OrderInterface $order,
        string $type = 'webhook'
    ): ProcessTransactionResponse {
        $mollieApi = $this->mollieApiClient->loadByStore((int)$order->getStoreId());
        $transactionId = $this->getTransactionId->forOrder($order);
        $mollieOrder = $mollieApi->orders->get($transactionId, ['embed' => 'payments']);
        $this->mollieHelper->addTolog($type, $mollieOrder);
        $status = $mollieOrder->status;

        $this->validateMetadata->execute($mollieOrder->metadata, $order);

        $defaultResponse = $this->processTransactionResponseFactory->create([
            'success' => true,
            'status' => $status,
            'order_id' => $order->getEntityId(),
            'type' => $type
        ]);

        $this->orderProcessors->process('preprocess', $order, $mollieOrder, $type, $defaultResponse);

        // This order is refunded, do not process any further.
        if ($mollieOrder->payments() &&
            $mollieOrder->payments()->offsetGet(0) &&
            isset($mollieOrder->payments()->offsetGet(0)->metadata->refunded)
        ) {
            return $this->orderProcessors->process(
                'previously_refunded',
                $order,
                $mollieOrder,
                $type,
                $defaultResponse
            );
        }

        if ($mollieOrder->isCompleted()) {
            return $this->orderProcessors->process(
                'is_completed',
                $order,
                $mollieOrder,
                $type,
                $defaultResponse
            );
        }

        $this->orderLines->updateOrderLinesByWebhook($mollieOrder->lines, $mollieOrder->isPaid());

        /**
         * Check if last payment was canceled, failed or expired and redirect customer to cart for retry.
         */
        $lastPaymentStatus = $this->mollieHelper->getLastRelevantStatus($mollieOrder);
        if ($lastPaymentStatus == 'canceled' || $lastPaymentStatus == 'failed' || $lastPaymentStatus == 'expired') {
            return $this->orderProcessors->process(
                'last_payment_status_is_failure',
                $order,
                $mollieOrder,
                $type,
                $defaultResponse
            );
        }

        $refunded = $mollieOrder->amountRefunded !== null;
        if (($mollieOrder->isPaid() || $mollieOrder->isAuthorized()) && !$refunded) {
            return $this->orderProcessors->process(
                'is_successful',
                $order,
                $mollieOrder,
                $type,
                $defaultResponse
            );
        }

        if ($refunded) {
            return $this->orderProcessors->process(
                'is_refunded',
                $order,
                $mollieOrder,
                $type,
                $this->processTransactionResponseFactory->create([
                    'success' => true,
                    'status' => 'refunded',
                    'order_id' => $order->getEntityId(),
                    'type' => $type
                ])
            );
        }

        if ($mollieOrder->isCreated()) {
            return $this->orderProcessors->process('created', $order, $mollieOrder, $type, $defaultResponse);
        }

        if ($mollieOrder->isCanceled()) {
            return $this->orderProcessors->process('cancelled', $order, $mollieOrder, $type, $defaultResponse);
        }

        if ($mollieOrder->isExpired()) {
            return $this->orderProcessors->process('expired', $order, $mollieOrder, $type, $defaultResponse);
        }

        if ($mollieOrder->isShipping()) {
            return $this->orderProcessors->process('shipping', $order, $mollieOrder, $type, $defaultResponse);
        }

        throw new LocalizedException(__('Unable to process order %s', $order->getIncrementId()));
    }
}
