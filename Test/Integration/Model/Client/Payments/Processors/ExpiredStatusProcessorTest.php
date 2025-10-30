<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Integration\Model\Client\Payments\Processors;

use Magento\Sales\Model\Order;
use Mollie\Api\Resources\Payment;
use Mollie\Payment\Api\Data\TransactionToOrderInterface;
use Mollie\Payment\Api\TransactionToOrderRepositoryInterface;
use Mollie\Payment\Model\Client\Payments\Processors\ExpiredStatusProcessor;
use Mollie\Payment\Model\Client\ProcessTransactionResponse;
use Mollie\Payment\Service\Order\ExpiredOrderToTransaction;
use Mollie\Payment\Test\Integration\IntegrationTestCase;
use Mollie\Payment\Test\Integration\MolliePaymentBuilder;

class ExpiredStatusProcessorTest extends IntegrationTestCase
{
    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     *
     * @return void
     */
    public function testCancelsWhenThereIsOnlyOneTransaction(): void
    {
        $transactionId = uniqid();

        $order = $this->loadOrderById('100000001');
        $order->setMollieTransactionId($transactionId);

        /** @var TransactionToOrderInterface $transactionToOrder */
        $transactionToOrder = $this->objectManager->create(TransactionToOrderInterface::class);
        $transactionToOrder->setOrderId((int)$order->getEntityId());
        $transactionToOrder->setTransactionId($transactionId);
        $this->objectManager->get(TransactionToOrderRepositoryInterface::class)->save($transactionToOrder);

        /** @var ExpiredStatusProcessor $instance */
        $instance = $this->objectManager->get(ExpiredStatusProcessor::class);
        $instance->process(
            $order,
            $this->getMolliePayment(),
            'webhook',
            $this->objectManager->create(ProcessTransactionResponse::class, [
                'success' => true,
                'status' => 'test',
                'order_id' => '-01',
                'type' => 'webhook',
            ]),
        );

        $this->assertEquals(Order::STATE_CANCELED, $order->getState());
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     *
     * @return void
     */
    public function testMarksTransactionAsSkippedWhenThereAreMultipleTransactions(): void
    {
        $transaction1 = uniqid();
        $transaction2 = uniqid();

        $order = $this->loadOrderById('100000001');
        $order->setMollieTransactionId($transaction1);

        /** @var TransactionToOrderInterface $transactionToOrder1 */
        $transactionToOrder1 = $this->objectManager->create(TransactionToOrderInterface::class);
        $transactionToOrder1->setOrderId((int)$order->getEntityId());
        $transactionToOrder1->setTransactionId($transaction1);
        $this->objectManager->get(TransactionToOrderRepositoryInterface::class)->save($transactionToOrder1);

        /** @var TransactionToOrderInterface $transactionToOrder2 */
        $transactionToOrder2 = $this->objectManager->create(TransactionToOrderInterface::class);
        $transactionToOrder2->setOrderId((int)$order->getEntityId());
        $transactionToOrder2->setTransactionId($transaction2);
        $this->objectManager->get(TransactionToOrderRepositoryInterface::class)->save($transactionToOrder2);

        /** @var ExpiredStatusProcessor $instance */
        $instance = $this->objectManager->get(ExpiredStatusProcessor::class);
        $instance->process(
            $order,
            $this->getMolliePayment(),
            'webhook',
            $this->objectManager->create(ProcessTransactionResponse::class, [
                'success' => true,
                'status' => 'test',
                'order_id' => '-01',
                'type' => 'webhook',
            ]),
        );

        /** @var ExpiredOrderToTransaction $transactionToOrder */
        $transactionToOrder = $this->objectManager->get(ExpiredOrderToTransaction::class);
        $transaction = $transactionToOrder->getByTransactionId($transaction1);

        $this->assertEquals(1, $transaction->getSkipped());
        $this->assertEquals(Order::STATE_PROCESSING, $order->getState());
    }

    private function getMolliePayment(): Payment
    {
        /** @var MolliePaymentBuilder $orderBuilder */
        $orderBuilder = $this->objectManager->create(MolliePaymentBuilder::class);
        $orderBuilder->setAmount(100, 'USD');
        $orderBuilder->setStatus('expired');

        return $orderBuilder->build();
    }
}
