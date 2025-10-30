<?php

/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Fakes\Model\Client\Payments;

use Magento\Sales\Api\Data\OrderInterface;
use Mollie\Payment\Helper\General as MollieHelper;
use Mollie\Payment\Model\Client\Payments\PaymentProcessors;
use Mollie\Payment\Model\Client\Payments\ProcessTransaction;
use Mollie\Payment\Model\Client\ProcessTransactionResponse;
use Mollie\Payment\Model\Client\ProcessTransactionResponseFactory;
use Mollie\Payment\Service\Mollie\MollieApiClient;
use Mollie\Payment\Service\Mollie\Order\GetTransactionId;

class ProcessTransactionFake extends ProcessTransaction
{
    private bool $shouldCallParent = true;

    private ProcessTransactionResponseFactory $processTransactionResponseFactory;

    private int $timesCalled = 0;

    public function __construct(
        ProcessTransactionResponseFactory $processTransactionResponseFactory,
        PaymentProcessors $paymentProcessors,
        MollieApiClient $mollieApiClient,
        MollieHelper $mollieHelper,
        GetTransactionId $getTransactionId,
    ) {
        parent::__construct(
            $processTransactionResponseFactory,
            $paymentProcessors,
            $mollieApiClient,
            $mollieHelper,
            $getTransactionId,
        );

        $this->processTransactionResponseFactory = $processTransactionResponseFactory;
    }

    public function disableParentCall(): void
    {
        $this->shouldCallParent = false;
    }

    public function getTimesCalled(): int
    {
        return $this->timesCalled;
    }

    public function execute(OrderInterface $magentoOrder, string $type = 'webhook'): ProcessTransactionResponse
    {
        $this->timesCalled++;

        if ($this->shouldCallParent) {
            return parent::execute($magentoOrder, $type);
        }

        return $this->processTransactionResponseFactory->create([
            'success' => false,
            'status' => 'fake',
            'order_id' => '-999',
            'type' => 'fake',
        ]);
    }
}
