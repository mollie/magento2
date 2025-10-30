<?php

/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Integration\Service\Mollie;

use Mollie\Payment\Queue\Publisher\PublishTransactionToProcess;
use Mollie\Payment\Service\Mollie\GetMollieStatus;
use Mollie\Payment\Service\Mollie\GetMollieStatusResult;
use Mollie\Payment\Service\Mollie\ProcessTransaction;
use Mollie\Payment\Test\Fakes\Queue\Publisher\PublishTransactionToProcessFake;
use Mollie\Payment\Test\Fakes\Service\Mollie\GetMollieStatusFake;
use Mollie\Payment\Test\Integration\IntegrationTestCase;

class ProcessTransactionTest extends IntegrationTestCase
{
    /**
     * @magentoConfigFixture current_store payment/mollie_general/process_transactions_in_the_queue 1
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testPublishesTask(): void
    {
        $order = $this->loadOrderById('100000001');

        $publisherFake = $this->objectManager->create(PublishTransactionToProcessFake::class);
        $this->objectManager->addSharedInstance($publisherFake, PublishTransactionToProcess::class);

        $mollieStatusFake = $this->objectManager->create(GetMollieStatusFake::class);
        $mollieStatusFake->setResponse($this->objectManager->create(GetMollieStatusResult::class, [
            'status' => 'paid',
            'method' => 'ideal',
        ]));
        $this->objectManager->addSharedInstance($mollieStatusFake, GetMollieStatus::class);

        $instance = $this->objectManager->create(ProcessTransaction::class);
        $instance->execute((int) $order->getId(), 'tr_123');

        $this->assertEquals(1, $publisherFake->getTimesCalled());
    }
}
