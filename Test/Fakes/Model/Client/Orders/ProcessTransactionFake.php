<?php

namespace Mollie\Payment\Test\Fakes\Model\Client\Orders;

use Magento\Sales\Api\Data\OrderInterface;
use Mollie\Payment\Helper\General as MollieHelper;
use Mollie\Payment\Model\Client\Orders\OrderProcessors;
use Mollie\Payment\Model\Client\Orders\ProcessTransaction;
use Mollie\Payment\Model\Client\ProcessTransactionResponse;
use Mollie\Payment\Model\Client\ProcessTransactionResponseFactory;
use Mollie\Payment\Model\OrderLines;
use Mollie\Payment\Service\Mollie\MollieApiClient;
use Mollie\Payment\Service\Mollie\ValidateMetadata;

class ProcessTransactionFake extends ProcessTransaction
{
    /**
     * @var bool
     */
    private $shouldCallParent = true;

    /**
     * @var ProcessTransactionResponseFactory
     */
    private $processTransactionResponseFactory;

    /**
     * @var int
     */
    private $timesCalled = 0;

    public function disableParentCall(): void
    {
        $this->shouldCallParent = false;
    }

    public function __construct(
        ProcessTransactionResponseFactory $processTransactionResponseFactory,
        OrderProcessors $orderProcessors,
        MollieApiClient $mollieApiClient,
        MollieHelper $mollieHelper,
        OrderLines $orderLines,
        ValidateMetadata $validateMetadata
    ) {
        parent::__construct(
            $processTransactionResponseFactory,
            $orderProcessors,
            $mollieApiClient,
            $mollieHelper,
            $orderLines,
            $validateMetadata
        );

        $this->processTransactionResponseFactory = $processTransactionResponseFactory;
    }

    public function getTimesCalled(): int
    {
        return $this->timesCalled;
    }

    public function execute(OrderInterface $order, string $type = 'webhook'): ProcessTransactionResponse
    {
        $this->timesCalled++;

        if ($this->shouldCallParent) {
            return parent::execute($order, $type);
        }

        return $this->processTransactionResponseFactory->create([
            'success' => false,
            'status' => 'fake',
            'order_id' => '-999',
            'type' => 'fake',
        ]);
    }
}
