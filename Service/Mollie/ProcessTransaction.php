<?php

/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Service\Mollie;

use Magento\Sales\Api\OrderRepositoryInterface;
use Mollie\Payment\Api\Data\TransactionToProcessInterface;
use Mollie\Payment\Api\Data\TransactionToProcessInterfaceFactory;
use Mollie\Payment\Config;
use Mollie\Payment\Model\Mollie;
use Mollie\Payment\Queue\Publisher\PublishTransactionToProcess;

class ProcessTransaction
{
    public function __construct(
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly Config $config,
        private readonly Mollie $mollieModel,
        private readonly TransactionToProcessInterfaceFactory $transactionToProcessFactory,
        private readonly PublishTransactionToProcess $publishTransactionToProcess,
        private readonly GetMollieStatus $getMollieStatus,
        private readonly GetMollieStatusResultFactory $getMollieStatusResultFactory,
    ) {
    }

    public function execute(int $orderId, string $transactionId, string $type = 'webhook'): GetMollieStatusResult
    {
        if ($this->config->processTransactionsInTheQueue()) {
            $this->queueOrder($orderId, $transactionId, $type);

            return $this->getMollieStatus->execute($orderId, $transactionId);
        }

        $order = $this->orderRepository->get($orderId);

        $order->setMollieTransactionId($transactionId);
        $result = $this->mollieModel->processTransactionForOrder($order, $type);

        return $this->getMollieStatusResultFactory->create([
            'status' => $result->getStatus(),
            'method' => $order->getPayment()->getAdditionalInformation('method') ?? $order->getPayment()->getMethod(),
        ]);
    }

    private function queueOrder(int $orderId, string $transactionId, string $type): void
    {
        /** @var TransactionToProcessInterface $data */
        $data = $this->transactionToProcessFactory->create();
        $data->setOrderId($orderId);
        $data->setTransactionId($transactionId);
        $data->setType($type);

        $this->publishTransactionToProcess->publish($data);
    }
}
