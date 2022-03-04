<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Model\Client\Orders\Processors;

use Magento\Sales\Api\Data\OrderInterface;
use Mollie\Api\Resources\Order;
use Mollie\Payment\Model\Client\OrderProcessorInterface;
use Mollie\Payment\Model\Client\ProcessTransactionResponse;
use Mollie\Payment\Model\Client\ProcessTransactionResponseFactory;
use Mollie\Payment\Service\Order\CancelOrder;

class CancelledProcessor implements OrderProcessorInterface
{
    /**
     * @var ProcessTransactionResponseFactory
     */
    private $processTransactionResponseFactory;

    /**
     * @var CancelOrder
     */
    private $cancelOrder;

    public function __construct(
        ProcessTransactionResponseFactory $processTransactionResponseFactory,
        CancelOrder $cancelOrder
    ) {
        $this->processTransactionResponseFactory = $processTransactionResponseFactory;
        $this->cancelOrder = $cancelOrder;
    }

    public function process(
        OrderInterface $order,
        Order $mollieOrder,
        string $type,
        ?ProcessTransactionResponse $response
    ): ?ProcessTransactionResponse {
        if ($type == 'webhook') {
            $this->cancelOrder->execute($order, $mollieOrder->status);
        }

        $result = [
            'success' => false,
            'status' => $mollieOrder->status,
            'order_id' => $order->getEntityId(),
            'type' => $type
        ];

        return $this->processTransactionResponseFactory->create($result);
    }
}
