<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Test\Fakes\Model\Client\Orders;

use Magento\Sales\Api\Data\OrderInterface;
use Mollie\Api\Resources\Order;
use Mollie\Payment\Config;
use Mollie\Payment\Model\Client\Orders\OrderProcessors;
use Mollie\Payment\Model\Client\ProcessTransactionResponse;
use Mollie\Payment\Model\Client\ProcessTransactionResponseFactory;

class OrderProcessorsFake extends OrderProcessors
{
    /**
     * @var bool
     */
    private $shouldCallParent = true;

    /**
     * @var array
     */
    private $calledEvents = [];

    /**
     * @var ProcessTransactionResponseFactory
     */
    private $processTransactionResponseFactory;

    public function __construct(
        Config $config,
        ProcessTransactionResponseFactory $processTransactionResponseFactory,
        array $processors
    ) {
        $this->processTransactionResponseFactory = $processTransactionResponseFactory;

        parent::__construct($config, $processTransactionResponseFactory, $processors);
    }

    public function enableParentCall(): void
    {
        $this->shouldCallParent = true;
    }

    public function disableParentCall(): void
    {
        $this->shouldCallParent = false;
    }

    public function getCalledEvents(): array
    {
        return array_unique($this->calledEvents);
    }

    public function process(
        string $event,
        OrderInterface $magentoOrder,
        Order $mollieOrder,
        string $type,
        ?ProcessTransactionResponse $response = null
    ): ?ProcessTransactionResponse {
        $this->calledEvents[] = $event;

        if ($this->shouldCallParent) {
            return parent::process($event, $magentoOrder, $mollieOrder, $type, $response);
        }

        return $this->processTransactionResponseFactory->create([
            'success' => false,
            'status' => 'fake',
            'order_id' => '-999',
            'type' => 'fake',
        ]);
    }
}
