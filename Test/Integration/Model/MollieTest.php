<?php

namespace Mollie\Payment\Model;

use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Mollie\Api\Exceptions\ApiException;
use Mollie\Payment\Model\Client\Orders;
use Mollie\Payment\Model\Client\Payments;
use Mollie\Payment\Test\Integration\TestCase;

class MollieTest extends TestCase
{
    public function processTransactionUsesTheCorrectApiProvider()
    {
        return [
            'orders' => ['ord_abcdefg', 'orders'],
            'payments' => ['tr_abcdefg', 'payments'],
        ];
    }

    /**
     * @dataProvider processTransactionUsesTheCorrectApiProvider
     * @magentoDataFixture Magento/Sales/_files/order.php
     * @magentoConfigFixture default_store payment/mollie_general/apikey_test test_dummyapikeywhichmustbe30characterslong
     * @magentoConfigFixture default_store payment/mollie_general/type test
     */
    public function testProcessTransactionUsesTheCorrectApi($orderId, $type)
    {
        $order = $this->loadOrder('100000001');
        $order->setMollieTransactionId($orderId);
        $this->objectManager->get(OrderRepositoryInterface::class)->save($order);

        $ordersApiMock = $this->createMock(Orders::class);
        $paymentsApiMock = $this->createMock(Payments::class);

        if ($type == 'orders') {
            $ordersApiMock->expects($this->once())->method('processTransaction');
        }

        if ($type == 'payments') {
            $paymentsApiMock->expects($this->once())->method('processTransaction');
        }

        /** @var Mollie $instance */
        $instance = $this->objectManager->create(Mollie::class, [
            'ordersApi' => $ordersApiMock,
            'paymentsApi' => $paymentsApiMock,
        ]);

        $instance->processTransaction($order->getEntityId());
    }

    public function testStartTransactionWithMethodOrder()
    {
        /** @var OrderInterface $order */
        $order = $this->objectManager->create(OrderInterface::class);

        $helperMock = $this->createMock(\Mollie\Payment\Helper\General::class);
        $helperMock->method('getApiKey')->willReturn('test_dummyapikeywhichmustbe30characterslong');
        $helperMock->method('getApiMethod')->willReturn('order');

        $ordersApiMock = $this->createMock(Orders::class);
        $ordersApiMock->method('startTransaction')->willReturn('order');

        $paymentsApiMock = $this->createMock(Payments::class);
        $paymentsApiMock->expects($this->never())->method('startTransaction');

        /** @var Mollie $instance */
        $instance = $this->objectManager->create(Mollie::class, [
            'ordersApi' => $ordersApiMock,
            'paymentsApi' => $paymentsApiMock,
            'mollieHelper' => $helperMock,
        ]);

        $result = $instance->startTransaction($order);

        $this->assertEquals('order', $result);
    }

    public function testStartTransactionWithMethodPayment()
    {
        /** @var OrderInterface $order */
        $order = $this->objectManager->create(OrderInterface::class);

        $helperMock = $this->createMock(\Mollie\Payment\Helper\General::class);
        $helperMock->method('getApiKey')->willReturn('test_dummyapikeywhichmustbe30characterslong');
        $helperMock->method('getApiMethod')->willReturn('payment');

        $ordersApiMock = $this->createMock(Orders::class);
        $ordersApiMock->expects($this->never())->method('startTransaction');

        $paymentsApiMock = $this->createMock(Payments::class);
        $paymentsApiMock->method('startTransaction')->willReturn('payment');

        /** @var Mollie $instance */
        $instance = $this->objectManager->create(Mollie::class, [
            'ordersApi' => $ordersApiMock,
            'paymentsApi' => $paymentsApiMock,
            'mollieHelper' => $helperMock,
        ]);

        $result = $instance->startTransaction($order);

        $this->assertEquals('payment', $result);
    }

    /**
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Mollie\Api\Exceptions\ApiException
     * @throws \ReflectionException
     */
    public function testRetriesOnACurlTimeout()
    {
        /** @var OrderInterface $order */
        $order = $this->objectManager->create(OrderInterface::class);

        $helperMock = $this->createMock(\Mollie\Payment\Helper\General::class);
        $helperMock->method('getApiKey')->willReturn('test_dummyapikeywhichmustbe30characterslong');
        $helperMock->method('getApiMethod')->willReturn('order');

        $ordersApiMock = $this->createMock(Orders::class);
        $ordersApiMock->expects($this->exactly(3))->method('startTransaction')->willThrowException(
            new ApiException(
                'cURL error 28: Connection timed out after 10074 milliseconds ' .
                '(see http://curl.haxx.se/libcurl/c/libcurl-errors.html)'
            )
        );

        $paymentsApiMock = $this->createMock(Payments::class);
        $paymentsApiMock->expects($this->once())->method('startTransaction')->willReturn('payment');

        /** @var Mollie $instance */
        $instance = $this->objectManager->create(Mollie::class, [
            'ordersApi' => $ordersApiMock,
            'paymentsApi' => $paymentsApiMock,
            'mollieHelper' => $helperMock,
        ]);

        $result = $instance->startTransaction($order);

        $this->assertEquals('payment', $result);
    }
}
