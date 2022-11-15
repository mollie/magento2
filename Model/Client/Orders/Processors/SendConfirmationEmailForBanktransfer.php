<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Model\Client\Orders\Processors;

use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Mollie\Api\Resources\Order;
use Mollie\Payment\Config;
use Mollie\Payment\Model\Client\OrderProcessorInterface;
use Mollie\Payment\Model\Client\ProcessTransactionResponse;
use Mollie\Payment\Model\Client\ProcessTransactionResponseFactory;

class SendConfirmationEmailForBanktransfer implements OrderProcessorInterface
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var ProcessTransactionResponseFactory
     */
    private $processTransactionResponseFactory;

    /**
     * @var OrderSender
     */
    private $orderSender;

    public function __construct(
        Config $config,
        ProcessTransactionResponseFactory $processTransactionResponseFactory,
        OrderSender $orderSender
    ) {
        $this->config = $config;
        $this->processTransactionResponseFactory = $processTransactionResponseFactory;
        $this->orderSender = $orderSender;
    }

    public function process(
        OrderInterface $order,
        Order $mollieOrder,
        string $type,
        ?ProcessTransactionResponse $response
    ): ?ProcessTransactionResponse {
        $response = $this->processTransactionResponseFactory->create([
            'success' => true,
            'status' => $mollieOrder->status,
            'order_id' => $order->getEntityId(),
            'type' => $type
        ]);

        if ($mollieOrder->method != 'banktransfer' || $order->getEmailSent()) {
            return $response;
        }

        try {
            $this->orderSender->send($order);
            $message = __('New order email sent');
        } catch (\Throwable $exception) {
            $message = __('Unable to send the new order email: %1', $exception->getMessage());
        }

        if (!$statusPending = $this->config->statusPendingBanktransfer($order->getStoreId())) {
            $statusPending = $order->getStatus();
        }

        $order->setState(\Magento\Sales\Model\Order::STATE_PENDING_PAYMENT);
        $order->addStatusToHistory($statusPending, $message, true);

        return $response;
    }
}
