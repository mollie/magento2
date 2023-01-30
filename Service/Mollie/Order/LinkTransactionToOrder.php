<?php

namespace Mollie\Payment\Service\Mollie\Order;

use Magento\Sales\Api\Data\OrderInterface;
use Mollie\Payment\Api\Data\TransactionToOrderInterfaceFactory;
use Mollie\Payment\Api\TransactionToOrderRepositoryInterface;

class LinkTransactionToOrder
{
    /**
     * @var TransactionToOrderRepositoryInterface
     */
    private $transactionToOrderRepository;

    /**
     * @var TransactionToOrderInterfaceFactory
     */
    private $transactionToOrderFactory;

    public function __construct(
        TransactionToOrderRepositoryInterface $transactionToOrderRepository,
        TransactionToOrderInterfaceFactory $transactionToOrderFactory
    ) {
        $this->transactionToOrderRepository = $transactionToOrderRepository;
        $this->transactionToOrderFactory = $transactionToOrderFactory;
    }

    public function execute(string $transactionId, OrderInterface $order): void
    {
        $this->transactionToOrderRepository->save(
            $this->transactionToOrderFactory->create()
                ->setTransactionId($transactionId)
                ->setOrderId($order->getEntityId())
        );

        $order->setMollieTransactionId($transactionId);
    }
}
