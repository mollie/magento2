<?php

namespace Mollie\Payment\Model\Client\Orders\Processors;

use Magento\Sales\Api\Data\OrderInterface;
use Mollie\Api\Resources\Order;
use Mollie\Payment\Model\Client\OrderProcessorInterface;
use Mollie\Payment\Model\Client\ProcessTransactionResponse;
use Mollie\Payment\Model\Client\ProcessTransactionResponseFactory;
use Mollie\Payment\Service\Order\ExpiredOrderToTransaction;

class ExpiredProcessor implements OrderProcessorInterface
{
    /**
     * @var CancelledProcessor
     */
    private $cancelledProcessor;

    /**
     * @var ProcessTransactionResponseFactory
     */
    private $processTransactionResponseFactory;

    /**
     * @var ExpiredOrderToTransaction
     */
    private $expiredOrderToTransaction;

    public function __construct(
        CancelledProcessor $cancelledProcessor,
        ExpiredOrderToTransaction $expiredOrderToTransaction,
        ProcessTransactionResponseFactory $processTransactionResponseFactory
    ) {
        $this->cancelledProcessor = $cancelledProcessor;
        $this->processTransactionResponseFactory = $processTransactionResponseFactory;
        $this->expiredOrderToTransaction = $expiredOrderToTransaction;
    }

    public function process(
        OrderInterface $magentoOrder,
        Order $mollieOrder,
        string $type,
        ProcessTransactionResponse $response
    ): ?ProcessTransactionResponse {
        if ($this->shouldCancelProcessing($magentoOrder)) {
            return $this->processTransactionResponseFactory->create([
                'success' => false,
                'status' => $mollieOrder->status,
                'order_id' => $magentoOrder->getEntityId(),
                'type' => $type
            ]);
        }

        return $this->cancelledProcessor->process($magentoOrder, $mollieOrder, $type, $response);
    }

    private function shouldCancelProcessing(OrderInterface $order): bool
    {
        if (!$this->expiredOrderToTransaction->hasMultipleTransactions($order)) {
            return false;
        }

        $this->expiredOrderToTransaction->markTransactionAsSkipped($order->getMollieTransactionId());
        return true;
    }
}
