<?php

namespace Mollie\Payment\Test\Integration\Model\Client;

use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Quote\Model\Quote;
use Magento\Sales\Api\Data\OrderAddressInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Email\Sender\InvoiceSender;
use Mollie\Api\Endpoints\OrderEndpoint;
use Mollie\Api\MollieApiClient;
use Mollie\Api\Types\OrderStatus;
use Mollie\Payment\Model\Client\Orders;
use Mollie\Payment\Model\Client\Orders\ProcessTransaction;
use Mollie\Payment\Model\OrderLines;
use Mollie\Payment\Service\Mollie\ValidateMetadata;
use Mollie\Payment\Test\Fakes\Service\Mollie\FakeMollieApiClient;
use Mollie\Payment\Test\Integration\IntegrationTestCase;
use stdClass;

class OrdersTest extends IntegrationTestCase
{
    /**
     * This key is invalid on purpose, as we can't work our way around the `new \Mollie\Api\MollieApiClient()` call.
     * It turns out that an invalid key also throws an exception, which is what we actually want in this case.
     *
     * @magentoConfigFixture default_store payment/mollie_general/apikey_test test_TEST_API_KEY
     * @magentoConfigFixture default_store payment/mollie_general/type test
     */
    public function testCancelOrderThrowsAnExceptionWithTheOrderIdIncluded()
    {
        /** @var Orders $instance */
        $instance = $this->objectManager->create(Orders::class);

        /** @var OrderInterface $order */
        $order = $this->objectManager->create(OrderInterface::class);
        $order->setEntityId(999);
        $order->setMollieTransactionId('MOLLIE-999');

        try {
            $instance->cancelOrder($order);
        } catch (\Magento\Framework\Exception\LocalizedException $exception) {
            $this->assertStringContainsString('Order ID: 999', $exception->getMessage());
            return;
        }

        $this->fail('We expected an exception but this was not thrown');
    }

    public function testRemovesEmptySpaceFromThePrefix()
    {
        /** @var Orders $instance */
        $instance = $this->objectManager->get(Orders::class);

        /** @var OrderAddressInterface $address */
        $address = $this->objectManager->get(OrderAddressInterface::class);

        $address->setPrefix('     ');
        $address->setStreet('');

        $result = $instance->getAddressLine($address);

        $this->assertEmpty($result['title']);
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Mollie\Api\Exceptions\ApiException
     */
    public function testProcessTransactionHandlesAStackOfPaymentsCorrectly()
    {
        $this->loadFakeEncryptor()->addReturnValue('', 'test_dummyapikeythatisvalidandislongenough');

        $mollieOrderMock = $this->mollieOrderMock('N/A', 'EUR');
        $mollieOrderMock->id = 'trx_test_123';
        $mollieOrderMock->lines = [];
        $mollieOrderMock->status = OrderStatus::STATUS_PAID;
        $mollieOrderMock->_embedded->payments = [];

        $mollieOrderMock->metadata = new stdClass;

        foreach (['cancelled', 'paid', 'expired'] as $status) {
            $payment = new stdClass;
            $payment->id = 'tr_fakeid';
            $payment->status = $status;

            $mollieOrderMock->_embedded->payments[] = $payment;
        }

        $orderEndpointMock = $this->createMock(OrderEndpoint::class);
        $orderEndpointMock->method('get')->willReturn($mollieOrderMock);

        $mollieApiMock = $this->createMock(MollieApiClient::class);
        $mollieApiMock->orders = $orderEndpointMock;

        /** @var FakeMollieApiClient $fakeMollieApiClient */
        $fakeMollieApiClient = $this->objectManager->get(FakeMollieApiClient::class);
        $fakeMollieApiClient->setInstance($mollieApiMock);

        $this->objectManager->addSharedInstance($fakeMollieApiClient, \Mollie\Payment\Service\Mollie\MollieApiClient::class);

        $invoiceSenderMock = $this->createMock(InvoiceSender::class);
        $invoiceSenderMock->method('send')->willReturn(true);

        $orderLinesMock = $this->createMock(OrderLines::class);

        $validateMetadataMock = $this->createMock(ValidateMetadata::class);
        $validateMetadataMock->method('execute');

        /** @var ProcessTransaction $instance */
        $instance = $this->objectManager->create(ProcessTransaction::class, [
            'invoiceSender' => $invoiceSenderMock,
            'orderLines' => $orderLinesMock,
            'validateMetadata' => $validateMetadataMock,
        ]);

        $order = $this->loadOrder('100000001');
        $order->setEmailSent(1);
        $order->setBaseCurrencyCode('EUR');
        $order->setOrderCurrencyCode('EUR');

        $result = $instance->execute($order)->toArray();

        $this->assertTrue($result['success']);
    }

    /**
     * @return \Mollie\Api\Resources\Order
     */
    protected function mollieOrderMock($status, $currency)
    {
        $mollieOrder = new \Mollie\Api\Resources\Order($this->createMock(MollieApiClient::class));
        $mollieOrder->status = $status;
        $mollieOrder->amountCaptured = new stdClass;
        $mollieOrder->amountCaptured->value = 50;
        $mollieOrder->amountCaptured->currency = 'EUR';

        $mollieOrder->amount = new stdClass();
        $mollieOrder->amount->value = 100;
        $mollieOrder->amount->currency = $currency;

        $mollieOrder->_embedded = new stdClass;
        $mollieOrder->_embedded->payments = [new stdClass];
        $mollieOrder->_embedded->payments[0]->id = 'tr_abc1234';
        $mollieOrder->_embedded->payments[0]->status = 'success';

        return $mollieOrder;
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/quote.php
     * @magentoDataFixture Magento/Sales/_files/order.php
     * @magentoConfigFixture default_store payment/mollie_methods_ideal/days_before_expire 5
     * @magentoConfigFixture default_store payment/mollie_methods_paymentlink/days_before_expire 6
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Mollie\Api\Exceptions\ApiException
     *
     * @dataProvider startTransactionIncludesTheExpiresAtParameterProvider
     */
    public function testStartTransactionIncludesTheExpiresAtParameter(
        string $method,
        int $days,
        array $limitedMethods
    ): void {
        $cart = $this->objectManager->create(Quote::class);
        $cart->load('test01', 'reserved_order_id');

        $order = $this->loadOrder('100000001');
        $order->setBaseCurrencyCode('EUR');
        $order->setQuoteId($cart->getId());
        $order->getPayment()->setMethod($method);
        if ($limitedMethods) {
            $order->getPayment()->setAdditionalInformation('limited_methods', $limitedMethods);
        }

        $mollieOrderMock = $this->createMock(\Mollie\Api\Resources\Order::class);
        $mollieOrderMock->id = 'abc123';

        $mollieApiMock = $this->createMock(MollieApiClient::class);
        $orderEndpointMock = $this->createMock(OrderEndpoint::class);
        $orderEndpointMock->method('create')->with( $this->callback(function ($orderData) use ($days) {
            $this->assertArrayHasKey('expiresAt', $orderData);
            $this->assertNotEmpty($orderData['expiresAt']);

            $now = $this->objectManager->create(TimezoneInterface::class)->scopeDate(null);
            $expected = $now->add(new \DateInterval('P' . $days . 'D'));

            $this->assertEquals($expected->format('Y-m-d'), $orderData['expiresAt']);

            return true;
        }))->willReturn($mollieOrderMock);

        $mollieApiMock->orders = $orderEndpointMock;

        /** @var Orders $instance */
        $instance = $this->objectManager->create(Orders::class, [
            'orderLines' => $this->createMock(\Mollie\Payment\Model\OrderLines::class),
        ]);

        $instance->startTransaction($order, $mollieApiMock);
    }

    public function startTransactionIncludesTheExpiresAtParameterProvider(): array
    {
        return [
            'ideal' =>
                ['mollie_methods_ideal', 5, []],
            'payment link with single method should use method' =>
                ['mollie_methods_paymentlink', 5, ['ideal']],
            'payment link with multiple methods should use payment link' =>
                ['mollie_methods_paymentlink', 6, ['ideal', 'creditcard']],
        ];
    }

    public function checksIfTheOrderHasAnUpdateProvider(): array
    {
        return [
            [OrderStatus::STATUS_CREATED, Order::STATE_NEW],
            [OrderStatus::STATUS_PAID, Order::STATE_PROCESSING],
            [OrderStatus::STATUS_AUTHORIZED, Order::STATE_PROCESSING],
            [OrderStatus::STATUS_CANCELED, Order::STATE_CANCELED],
            [OrderStatus::STATUS_SHIPPING, Order::STATE_PROCESSING],
            [OrderStatus::STATUS_COMPLETED, Order::STATE_COMPLETE],
            [OrderStatus::STATUS_EXPIRED, Order::STATE_CANCELED],
            [OrderStatus::STATUS_PENDING, Order::STATE_PENDING_PAYMENT],
            [OrderStatus::STATUS_REFUNDED, Order::STATE_CLOSED],
        ];
    }

    /**
     * @dataProvider checksIfTheOrderHasAnUpdateProvider
     */
    public function testChecksIfTheOrderHasAnUpdate($mollieStatus, $magentoStatus)
    {
        /** @var OrderInterface $order */
        $order = $this->objectManager->create(OrderInterface::class);

        $mollieApi = new MollieApiClient();
        $mollieOrder = new \Mollie\Api\Resources\Order($mollieApi);

        $ordersApiMock = $this->createMock(OrderEndpoint::class);
        $ordersApiMock->method('get')->willReturn($mollieOrder);
        $mollieApi->orders = $ordersApiMock;

        $mollieOrder->status = $mollieStatus;
        $order->setState($magentoStatus);

        /** @var Orders $instance */
        $instance = $this->objectManager->create(Orders::class);

        $this->assertFalse($instance->orderHasUpdate($order, $mollieApi));
    }
}
