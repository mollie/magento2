<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Model\Client\Orders;

use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\OrderInterface;
use Mollie\Api\Resources\Order;
use Mollie\Payment\Config;
use Mollie\Payment\Model\Client\OrderProcessorInterface;
use Mollie\Payment\Model\Client\ProcessTransactionResponse;
use Mollie\Payment\Model\Client\ProcessTransactionResponseFactory;

class OrderProcessors
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
     * @var array
     */
    private $processors;

    public function __construct(
        Config $config,
        ProcessTransactionResponseFactory $processTransactionResponseFactory,
        array $processors
    ) {
        $this->config = $config;
        $this->processTransactionResponseFactory = $processTransactionResponseFactory;
        $this->processors = $processors;
    }

    public function process(
        string $event,
        OrderInterface $magentoOrder,
        Order $mollieOrder,
        string $type,
        ProcessTransactionResponse $response
    ): ?ProcessTransactionResponse {
        if (!isset($this->processors[$event])) {
            $this->config->addToLog('success', $response->toArray());
            return $response;
        }

        foreach ($this->processors[$event] as $name => $processor) {
            if (!$processor instanceof OrderProcessorInterface) {
                throw new LocalizedException(__('"%1" does not implement %1', $name, OrderProcessorInterface::class));
            }

            $response = $processor->process($magentoOrder, $mollieOrder, $type, $response);
        }

        if (!$response) {
            $response = $this->returnResponse($mollieOrder, $magentoOrder, $type);
        }

        $this->config->addToLog('success', $response->toArray());
        return $response;
    }

    /**
     * @param Order $mollieOrder
     * @param OrderInterface $magentoOrder
     * @param string $type
     * @return ProcessTransactionResponse
     */
    protected function returnResponse(Order $mollieOrder, OrderInterface $magentoOrder, string $type): ProcessTransactionResponse
    {
        return $this->processTransactionResponseFactory->create([
            'success' => false,
            'status' => $mollieOrder->status,
            'order_id' => $magentoOrder->getEntityId(),
            'type' => $type,
        ]);
    }
}
