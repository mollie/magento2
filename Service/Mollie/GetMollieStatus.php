<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Service\Mollie;

use Magento\Sales\Api\OrderRepositoryInterface;

class GetMollieStatus
{
    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;
    /**
     * @var MollieApiClient
     */
    private $mollieApiClient;
    /**
     * @var GetMollieStatusResultFactory
     */
    private $getMollieStatusResultFactory;

    public function __construct(
        OrderRepositoryInterface $orderRepository,
        MollieApiClient $mollieApiClient,
        GetMollieStatusResultFactory $getMollieStatusResultFactory
    ) {
        $this->orderRepository = $orderRepository;
        $this->mollieApiClient = $mollieApiClient;
        $this->getMollieStatusResultFactory = $getMollieStatusResultFactory;
    }

    public function execute(int $orderId, ?string $transactionId = null): GetMollieStatusResult
    {
        $order = $this->orderRepository->get($orderId);
        if ($transactionId === null) {
            $transactionId = $order->getMollieTransactionId();
        }

        if ($transactionId === null) {
            throw new \Exception('No transaction ID found for order ' . $orderId);
        }

        $mollieApi = $this->mollieApiClient->loadByStore((int)$order->getStoreId());

        if (substr($transactionId, 0, 4) == 'ord_') {
            $mollieOrder = $mollieApi->orders->get($transactionId);

            return $this->getMollieStatusResultFactory->create([
                'status' => $mollieOrder->status,
                'method' => $mollieOrder->method,
            ]);
        }

        $molliePayment = $mollieApi->payments->get($transactionId);
        return $this->getMollieStatusResultFactory->create([
            'status' => $molliePayment->status,
            'method' => $molliePayment->method,
        ]);
    }
}
