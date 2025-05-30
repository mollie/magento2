<?php

namespace Mollie\Payment\Test\Unit\Service\Mollie\Order;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Mollie\Api\Endpoints\OrderEndpoint;
use Mollie\Api\Endpoints\PaymentEndpoint;
use Mollie\Api\MollieApiClient;
use Mollie\Api\Resources\Order as MollieOrder;
use Mollie\Api\Resources\Payment;
use Mollie\Api\Resources\PaymentFactory;
use Mollie\Payment\Helper\General;
use Mollie\Payment\Service\Mollie\Order\RefundUsingPayment;
use Mollie\Payment\Test\Unit\UnitTestCase;

class RefundUsingPaymentTest extends UnitTestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    private $paymentEndpoint;

    /**
     * @var RefundUsingPayment
     */
    private $instance;

    protected function setUpWithoutVoid()
    {
        $mockBuilder = $this->getMockBuilder(PaymentFactory::class);
        $mockBuilder->disableOriginalConstructor();
        $mockBuilder->onlyMethods(['create']);
        $paymentFactory = $mockBuilder->getMock();
        $paymentFactory->method('create')->willReturn($this->createMock(Payment::class));

        $this->instance = $this->objectManager->getObject(RefundUsingPayment::class, [
            'paymentFactory' => $paymentFactory,
            'mollieHelper' => $this->objectManager->getObject(General::class),
        ]);
    }

    public function testExecute()
    {
        $mollieApi = $this->buildMollieApiMock();

        $this->paymentEndpoint->expects($this->once())->method('refund')->with(
            $this->isInstanceOf(Payment::class), // $payment
            $this->callback(function ($argument) {
                $this->assertSame('100.00', $argument['amount']['value']);
                return true;
            }) // $data
        );

        $this->instance->execute($mollieApi, 999, 'EUR', 100);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    private function buildMollieApiMock()
    {
        $mollieOrder = $this->createMock(MollieOrder::class);
        $mollieOrder->_embedded = new \stdClass();

        $payment = new \stdClass();
        $payment->id = 123;
        $mollieOrder->_embedded->payments = [$payment];

        $this->paymentEndpoint = $this->createMock(PaymentEndpoint::class);

        $mollieApi = $this->createMock(MollieApiClient::class);
        $orderEndpoint = $this->createMock(OrderEndpoint::class);
        $orderEndpoint->method('get')->willReturn($mollieOrder);
        $mollieApi->orders = $orderEndpoint;
        $mollieApi->payments = $this->paymentEndpoint;

        return $mollieApi;
    }
}
