<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Model\Client\Orders\Processors;

use Magento\Sales\Api\Data\OrderInterface;
use Mollie\Api\Resources\Order;
use Mollie\Payment\Config;
use Mollie\Payment\Model\Client\OrderProcessorInterface;
use Mollie\Payment\Model\Client\ProcessTransactionResponse;
use Mollie\Payment\Model\Client\ProcessTransactionResponseFactory;
use Mollie\Payment\Service\Order\SendOrderEmails;

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
     * @var SendOrderEmails
     */
    private $sendOrderEmails;

    public function __construct(
        Config $config,
        ProcessTransactionResponseFactory $processTransactionResponseFactory,
        SendOrderEmails $sendOrderEmails
    ) {
        $this->config = $config;
        $this->processTransactionResponseFactory = $processTransactionResponseFactory;
        $this->sendOrderEmails = $sendOrderEmails;
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

        if (!$statusPending = $this->config->statusPendingBanktransfer($order->getStoreId())) {
            $statusPending = $order->getStatus();
        }

        $order->setStatus($statusPending);
        $order->setState(\Magento\Sales\Model\Order::STATE_PENDING_PAYMENT);

        $this->sendOrderEmails->sendOrderConfirmation($order);

        return $response;
    }
}
