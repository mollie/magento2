<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Integration\Model\Client;

use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Email\Sender\InvoiceSender;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Mollie\Api\Exceptions\ApiException;
use Mollie\Api\Fake\MockResponse;
use Mollie\Api\Http\Requests\GetPaymentRequest;
use Mollie\Api\MollieApiClient;
use Mollie\Api\Resources\Payment;
use Mollie\Api\Types\PaymentStatus;
use Mollie\Payment\Api\Data\TransactionToOrderInterface;
use Mollie\Payment\Api\TransactionToOrderRepositoryInterface;
use Mollie\Payment\Model\Client\Payments;
use Mollie\Payment\Model\Client\Payments\ProcessTransaction;
use Mollie\Payment\Model\OrderLines;
use Mollie\Payment\Test\Fakes\Service\Mollie\FakeMollieApiClient;
use Mollie\Payment\Test\Integration\IntegrationTestCase;
use stdClass;

class PaymentsTest extends IntegrationTestCase
{
    public function processTransactionProvider(): array
    {
        return [
            ['USD'],
        ];
    }

    /**
     * @dataProvider processTransactionProvider
     * @magentoDataFixture Magento/Sales/_files/order.php
     *
     * @param string $currency
     *
     * @throws LocalizedException
     * @throws ApiException
     */
    public function testProcessTransaction(string $currency): void
    {
        $client = MollieApiClient::fake([
            GetPaymentRequest::class => MockResponse::ok('payment'),
        ]);

        /** @var FakeMollieApiClient $fakeMollieApiClient */
        $fakeMollieApiClient = $this->objectManager->get(FakeMollieApiClient::class);
        $fakeMollieApiClient->setInstance($client);

        $this->objectManager->addSharedInstance($fakeMollieApiClient, \Mollie\Payment\Service\Mollie\MollieApiClient::class);

        $orderLinesMock = $this->createMock(OrderLines::class);

        $orderSenderMock = $this->createMock(OrderSender::class);
        $orderSenderMock->method('send')->willReturn(true);

        $invoiceSenderMock = $this->createMock(InvoiceSender::class);
        $invoiceSenderMock->method('send')->willReturn(true);

        /** @var Payments\ProcessTransaction $instance */
        $instance = $this->objectManager->create(ProcessTransaction::class, [
            'orderLines' => $orderLinesMock,
            'orderSender' => $orderSenderMock,
            'invoiceSender' => $invoiceSenderMock,
        ]);

        $order = $this->loadOrder('100000001');
        $order->setMollieTransactionId('MOLLIE_TEST_TRANSACTION');
        $order->setBaseCurrencyCode($currency);
        $order->setOrderCurrencyCode($currency);

        /** @var TransactionToOrderInterface $model */
        $model = $this->objectManager->create(TransactionToOrderInterface::class);
        $model->setTransactionId('MOLLIE_TEST_TRANSACTION');
        $model->setOrderId((int)$order->getId());

        $this->objectManager->get(TransactionToOrderRepositoryInterface::class)->save($model);

        $instance->execute($order)->toArray();

        $this->assertEquals(Order::STATE_PROCESSING, $order->getState());
    }

    /**
     * @return Payment
     */
    protected function getMolliePayment($status, $currency): Payment
    {
        $payment = new Payment($this->createMock(MollieApiClient::class));
        $payment->id = 'tr_test_transaction';
        $payment->status = $status;
        $payment->settlementAmount = new stdClass();
        $payment->settlementAmount->value = 50;
        $payment->settlementAmount->currency = 'EUR';

        $payment->amount = new stdClass();
        $payment->amount->value = 100;
        $payment->amount->currency = $currency;

        $payment->_embedded = new stdClass();
        $payment->_embedded->payments = [new stdClass()];
        $payment->_embedded->payments[0]->status = 'success';

        return $payment;
    }

    public function checksIfTheOrderHasAnUpdateProvider(): array
    {
        return [
            [PaymentStatus::OPEN, Order::STATE_NEW],
            [PaymentStatus::PENDING, Order::STATE_PENDING_PAYMENT],
            [PaymentStatus::AUTHORIZED, Order::STATE_PROCESSING],
            [PaymentStatus::CANCELED, Order::STATE_CANCELED],
            [PaymentStatus::EXPIRED, Order::STATE_CLOSED],
            [PaymentStatus::PAID, Order::STATE_PROCESSING],
            [PaymentStatus::FAILED, Order::STATE_CANCELED],
        ];
    }

    /**
     * @dataProvider checksIfTheOrderHasAnUpdateProvider
     */
    public function testChecksIfTheOrderHasAnUpdate(string $mollieStatus, string $magentoStatus): void
    {
        /** @var OrderInterface $order */
        $order = $this->objectManager->create(OrderInterface::class);
        $order->setMollieTransactionId('MOLLIE_TEST_TRANSACTION');
        $order->setState($magentoStatus);

        /** @var Payments $instance */
        $instance = $this->objectManager->create(Payments::class);

        $client = MollieApiClient::fake([
            GetPaymentRequest::class => MockResponse::ok('{"status":"' . $mollieStatus . '"}'),
        ]);

        $this->assertFalse($instance->orderHasUpdate($order, $client));
    }
}
