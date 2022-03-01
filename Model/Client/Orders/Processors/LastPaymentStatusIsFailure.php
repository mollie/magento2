<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Model\Client\Orders\Processors;

use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Mollie\Api\Resources\Order;
use Mollie\Payment\Helper\General;
use Mollie\Payment\Model\Client\OrderProcessorInterface;
use Mollie\Payment\Model\Client\ProcessTransactionResponse;
use Mollie\Payment\Model\Client\ProcessTransactionResponseFactory;
use Mollie\Payment\Service\Order\CancelOrder;
use Mollie\Payment\Service\Order\TransactionProcessor;

class LastPaymentStatusIsFailure implements OrderProcessorInterface
{
    /**
     * @var General
     */
    private $mollieHelper;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var CancelOrder
     */
    private $cancelOrder;

    /**
     * @var TransactionProcessor
     */
    private $transactionProcessor;

    /**
     * @var ProcessTransactionResponseFactory
     */
    private $processTransactionResponseFactory;

    public function __construct(
        General $mollieHelper,
        OrderRepositoryInterface $orderRepository,
        CancelOrder $cancelOrder,
        TransactionProcessor $transactionProcessor,
        ProcessTransactionResponseFactory $processTransactionResponseFactory
    ) {
        $this->mollieHelper = $mollieHelper;
        $this->orderRepository = $orderRepository;
        $this->cancelOrder = $cancelOrder;
        $this->transactionProcessor = $transactionProcessor;
        $this->processTransactionResponseFactory = $processTransactionResponseFactory;
    }

    public function process(
        OrderInterface $magentoOrder,
        Order $mollieOrder,
        string $type,
        ProcessTransactionResponse $response
    ): ?ProcessTransactionResponse {
        $lastPaymentStatus = $this->mollieHelper->getLastRelevantStatus($mollieOrder);
        $method = $magentoOrder->getPayment()->getMethodInstance()->getTitle();
        $magentoOrder->getPayment()->setAdditionalInformation('payment_status', $lastPaymentStatus);
        $this->orderRepository->save($magentoOrder);
        $this->cancelOrder->execute($magentoOrder, $lastPaymentStatus);
        $this->transactionProcessor->process($magentoOrder, $mollieOrder);

        $result = [
            'success' => false,
            'status' => $lastPaymentStatus,
            'order_id' => $magentoOrder->getEntityId(),
            'type' => $type,
            'method' => $method
        ];

        $this->mollieHelper->addTolog('success', $result);
        return $this->processTransactionResponseFactory->create($result);
    }
}
