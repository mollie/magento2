<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Model\Client\Payments;

use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\OrderInterface;
use Mollie\Api\Resources\Payment;
use Mollie\Payment\Config;
use Mollie\Payment\Model\Client\PaymentProcessorInterface;
use Mollie\Payment\Model\Client\ProcessTransactionResponse;
use Mollie\Payment\Model\Client\ProcessTransactionResponseFactory;

class PaymentProcessors
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
        Payment $molliePayment,
        string $type,
        ProcessTransactionResponse $response
    ): ?ProcessTransactionResponse {
        if (!isset($this->processors[$event])) {
            $this->config->addToLog('success', $response->toArray());
            return $response;
        }

        foreach ($this->processors[$event] as $name => $processor) {
            if (!$processor instanceof PaymentProcessorInterface) {
                throw new LocalizedException(__('"%1" does not implement %1', $name, PaymentProcessorInterface::class));
            }

            $response = $processor->process($magentoOrder, $molliePayment, $type, $response);
        }

        if (!$response) {
            $response = $this->returnResponse($molliePayment, $magentoOrder, $type);
        }

        $this->config->addToLog('success', $response->toArray());
        return $response;
    }

    /**
     * @param Payment $molliePayment
     * @param OrderInterface $magentoOrder
     * @param string $type
     * @return ProcessTransactionResponse
     */
    protected function returnResponse(Payment $molliePayment, OrderInterface $magentoOrder, string $type): ProcessTransactionResponse
    {
        return $this->processTransactionResponseFactory->create([
            'success' => false,
            'status' => $molliePayment->status,
            'order_id' => $magentoOrder->getEntityId(),
            'type' => $type,
        ]);
    }
}
