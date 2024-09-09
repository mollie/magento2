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
    /**
     * @var Mollie
     */
    private $mollieModel;
    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;
    /**
     * @var Config
     */
    private $config;
    /**
     * @var TransactionToProcessInterfaceFactory
     */
    private $transactionToProcessFactory;
    /**
     * @var PublishTransactionToProcess
     */
    private $publishTransactionToProcess;
    /**
     * @var GetMollieStatusResultFactory
     */
    private $getMollieStatusResultFactory;
    /**
     * @var GetMollieStatus
     */
    private $getMollieStatus;

    public function __construct(
        OrderRepositoryInterface $orderRepository,
        Config $config,
        Mollie $mollieModel,
        TransactionToProcessInterfaceFactory $transactionToProcessFactory,
        PublishTransactionToProcess $publishTransactionToProcess,
        GetMollieStatus $getMollieStatus,
        GetMollieStatusResultFactory $getMollieStatusResultFactory
    ) {
        $this->mollieModel = $mollieModel;
        $this->orderRepository = $orderRepository;
        $this->config = $config;
        $this->transactionToProcessFactory = $transactionToProcessFactory;
        $this->publishTransactionToProcess = $publishTransactionToProcess;
        $this->getMollieStatusResultFactory = $getMollieStatusResultFactory;
        $this->getMollieStatus = $getMollieStatus;
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
            'status' => $result['status'],
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
