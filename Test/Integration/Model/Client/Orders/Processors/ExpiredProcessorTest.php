<?php

namespace Mollie\Payment\Test\Integration\Model\Client\Orders\Processors;

use Magento\Sales\Model\Order;
use Mollie\Payment\Api\Data\TransactionToOrderInterface;
use Mollie\Payment\Api\TransactionToOrderRepositoryInterface;
use Mollie\Payment\Model\Client\Orders\Processors\ExpiredProcessor;
use Mollie\Payment\Model\Client\ProcessTransactionResponse;
use Mollie\Payment\Service\Order\ExpiredOrderToTransaction;
use Mollie\Payment\Test\Integration\IntegrationTestCase;
use Mollie\Payment\Test\Integration\MollieOrderBuilder;

class ExpiredProcessorTest extends IntegrationTestCase
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
        $transactionToOrder->setOrderId($order->getEntityId());
        $transactionToOrder->setTransactionId($transactionId);
        $this->objectManager->get(TransactionToOrderRepositoryInterface::class)->save($transactionToOrder);

        /** @var ExpiredProcessor $instance */
        $instance = $this->objectManager->get(ExpiredProcessor::class);
        $instance->process(
            $order,
            $this->getMollieOrder(),
            'webhook',
            $this->objectManager->create(ProcessTransactionResponse::class, [
                'success' => true,
                'status' => 'test',
                'order_id' => '-01',
                'type' => 'webhook',
            ])
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
        $transactionToOrder1->setOrderId($order->getEntityId());
        $transactionToOrder1->setTransactionId($transaction1);
        $this->objectManager->get(TransactionToOrderRepositoryInterface::class)->save($transactionToOrder1);

        /** @var TransactionToOrderInterface $transactionToOrder2 */
        $transactionToOrder2 = $this->objectManager->create(TransactionToOrderInterface::class);
        $transactionToOrder2->setOrderId($order->getEntityId());
        $transactionToOrder2->setTransactionId($transaction2);
        $this->objectManager->get(TransactionToOrderRepositoryInterface::class)->save($transactionToOrder2);

        /** @var ExpiredProcessor $instance */
        $instance = $this->objectManager->get(ExpiredProcessor::class);
        $instance->process(
            $order,
            $this->getMollieOrder(),
            'webhook',
            $this->objectManager->create(ProcessTransactionResponse::class, [
                'success' => true,
                'status' => 'test',
                'order_id' => '-01',
                'type' => 'webhook',
            ])
        );

        /** @var OrderToTransaction $transactionToOrder */
        $transactionToOrder = $this->objectManager->get(ExpiredOrderToTransaction::class);
        $transaction = $transactionToOrder->getByTransactionId($transaction1);

        $this->assertEquals(1, $transaction->getSkipped());
        $this->assertEquals(Order::STATE_PROCESSING, $order->getState());
    }

    private function getMollieOrder(): \Mollie\Api\Resources\Order
    {
        /** @var MollieOrderBuilder $orderBuilder */
        $orderBuilder = $this->objectManager->create(MollieOrderBuilder::class);
        $orderBuilder->setAmount(100, 'USD');
        $orderBuilder->setStatus('expired');

        return $orderBuilder->build();
    }
}
