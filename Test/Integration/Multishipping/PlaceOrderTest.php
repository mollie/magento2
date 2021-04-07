<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Test\Integration\Multishipping;

use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Sales\Api\OrderManagementInterface;
use Mollie\Api\Endpoints\PaymentEndpoint;
use Mollie\Api\MollieApiClient;
use Mollie\Api\Resources\Payment;
use Mollie\Payment\Model\Client\Payments;
use Mollie\Payment\Model\Mollie;
use Mollie\Payment\Multishipping\PlaceOrder;
use Mollie\Payment\Test\Integration\IntegrationTestCase;

class PlaceOrderTest extends IntegrationTestCase
{
    public function testPlacesTheOrders()
    {
        $orders = [
            $this->objectManager->create(OrderInterface::class),
            $this->objectManager->create(OrderInterface::class),
        ];

        /** @var OrderInterface $order */
        foreach ($orders as $order) {
            $order->setPayment($this->objectManager->create(OrderPaymentInterface::class)->setMethod('mollie_methods_ideal'));
        }

        $orderManagementMock = $this->createMock(OrderManagementInterface::class);
        $orderManagementMock->expects($this->exactly(2))->method('place');

        $paymentEndpointMock = $this->createMock(PaymentEndpoint::class);

        $mollieApi = new MollieApiClient();
        $mollieApi->payments = $paymentEndpointMock;

        $paymentEndpointMock->expects($this->once())->method('create')->willReturn(new Payment($mollieApi));

        $mollieModelMock = $this->createMock(Mollie::class);
        $mollieModelMock->method('getMollieApi')->willReturn($mollieApi);

        /** @var PlaceOrder $instance */
        $instance = $this->objectManager->create(PlaceOrder::class, [
            'orderManagement' => $orderManagementMock,
            'molliePaymentsApi' => $this->createMock(Payments::class),
            'mollieModel' => $mollieModelMock,
        ]);

        $result = $instance->place($orders);

        $this->assertCount(0, $result);
    }

    public function testReturnsAnyErrors()
    {
        $orders = [
            $this->objectManager->create(OrderInterface::class)->setIncrementId(10000000000001),
            $this->objectManager->create(OrderInterface::class)->setIncrementId(10000000000002),
        ];

        $orderManagementMock = $this->createMock(OrderManagementInterface::class);
        $orderManagementMock->method('place')->willThrowException(new \Exception('This is a test exception'));

        /** @var PlaceOrder $instance */
        $instance = $this->objectManager->create(PlaceOrder::class, [
            'orderManagement' => $orderManagementMock,
        ]);

        $result = $instance->place($orders);

        $this->assertCount(2, $result);
        $exception = array_shift($result);
        $this->assertEquals('This is a test exception', $exception->getMessage());
    }
}
