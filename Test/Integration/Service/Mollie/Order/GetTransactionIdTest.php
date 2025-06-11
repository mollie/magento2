<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Integration\Service\Mollie\Order;

use Mollie\Api\Resources\Payment;
use Mollie\Payment\Api\Data\TransactionToOrderInterface;
use Mollie\Payment\Api\TransactionToOrderRepositoryInterface;
use Mollie\Payment\Service\Mollie\MollieApiClient;
use Mollie\Payment\Service\Mollie\Order\GetTransactionId;
use Mollie\Payment\Test\Fakes\Service\Mollie\FakeMollieApiClient;
use Mollie\Payment\Test\Integration\IntegrationTestCase;

class GetTransactionIdTest extends IntegrationTestCase
{
    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     * @return void
     */
    public function testDoesNothingWhenNoTransactionIsAvailable(): void
    {
        $order = $this->loadOrderById('100000001');

        $instance = $this->objectManager->create(GetTransactionId::class);
        $instance->forOrder($order);

        $this->assertNull(
            $order->getMollieTransactionId(),
            'Transaction ID should not be set when no transaction is available'
        );
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     * @dataProvider usesTheFirstPaidTransactionDataProvider
     * @return void
     */
    public function testUsesTheFirstPaidTransaction(array $transactions, string $paid): void
    {
        $order = $this->loadOrderById('100000001');

        $payments = [];
        foreach ($transactions as $transaction) {
            $payments[] = $this->addMollieTransactionToOrder((int)$order->getId(), $transaction[0], $transaction[1]);
        }

        /** @var FakeMollieApiClient $fakeMollieApiClient */
        $fakeMollieApiClient = $this->objectManager->create(FakeMollieApiClient::class);
        $fakeMollieApiClient->setInstance(new \Mollie\Api\MollieApiClient);
        $fakeMollieApiClient->returnFakePayment($payments[0]);

        foreach ($payments as $payment) {
            $fakeMollieApiClient->loadByStore()->payments->setFakePayment($payment);
        }

        $this->objectManager->addSharedInstance($fakeMollieApiClient, MollieApiClient::class);

        $instance = $this->objectManager->create(GetTransactionId::class);
        $instance->forOrder($order);

        $this->assertEquals(
            $paid,
            $order->getMollieTransactionId()
        );
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     * @return void
     */
    public function testDoesNotOverrideWhenNoTransactionsArePaid(): void
    {
        $order = $this->loadOrderById('100000001');
        $order->setMollieTransactionId('tr_aaa111');

        $payments = [];
        $payments[] = $this->addMollieTransactionToOrder((int)$order->getId(), 'tr_abc123', 'pending');
        $payments[] = $this->addMollieTransactionToOrder((int)$order->getId(), 'tr_def456', 'pending');

        /** @var FakeMollieApiClient $fakeMollieApiClient */
        $fakeMollieApiClient = $this->objectManager->create(FakeMollieApiClient::class);
        $fakeMollieApiClient->setInstance(new \Mollie\Api\MollieApiClient);
        $fakeMollieApiClient->returnFakePayment($payments[0]);

        foreach ($payments as $payment) {
            $fakeMollieApiClient->loadByStore()->payments->setFakePayment($payment);
        }

        $this->objectManager->addSharedInstance($fakeMollieApiClient, MollieApiClient::class);

        $instance = $this->objectManager->create(GetTransactionId::class);
        $instance->forOrder($order);

        $this->assertEquals(
            'tr_aaa111',
            $order->getMollieTransactionId()
        );
    }

    public function usesTheFirstPaidTransactionDataProvider(): array
    {
        return [
            [
                'transactions' => [['tr_abc123', 'paid'], ['tr_def465', 'pending'], ['tr_ghi678', 'canceled']],
                'paid' => 'tr_abc123',
            ],
            [
                'transactions' => [['tr_abc123', 'pending'], ['tr_def465', 'paid'], ['tr_ghi678', 'canceled']],
                'paid' => 'tr_def465',
            ],
            [
                'transactions' => [['tr_abc123', 'pending'], ['tr_def465', 'paid'], ['tr_ghi678', 'paid']],
                'paid' => 'tr_def465',
            ],
            [
                'transactions' => [['tr_abc123', 'pending'], ['tr_def465', 'pending'], ['tr_ghi678', 'paid']],
                'paid' => 'tr_ghi678',
            ],
        ];
    }

    private function addMollieTransactionToOrder(int $orderId, string $transactionId, string $status): Payment
    {
        /** @var TransactionToOrderInterface $model */
        $model = $this->objectManager->create(TransactionToOrderInterface::class);
        $model->setTransactionId($transactionId);
        $model->setOrderId($orderId);

        $this->objectManager->get(TransactionToOrderRepositoryInterface::class)->save($model);

        $payment = new Payment(new \Mollie\Api\MollieApiClient());
        $payment->id = $transactionId;
        $payment->status = $status;

        return $payment;
    }
}
