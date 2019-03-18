<?php

namespace Mollie\Payment\Tests\Unit\Model;

use Mollie\Payment\Model\Mollie;
use Mollie\Payment\Tests\Unit\TestCase;

class MollieTest extends TestCase
{
    public function testThePaymentsApiIsUsedWhenTheOrdersApiFails()
    {
        $paymentsApiMock = $this->createMock(\Mollie\Payment\Model\Client\Payments::class);
        $paymentsApiMock->expects($this->once())->method('startTransaction')->willReturn('succesfull call');

        /** @var Mollie $instance */
        $instance = $this->get(Mollie::class, [
            'directory' => $this->createMock(\Magento\Directory\Helper\Data::class),
            'mollieHelper' => $this->getHelperMock(),
            'ordersApi' => $this->getOrderMock(),
            'paymentsApi' => $paymentsApiMock,
        ]);

        $order = $this->createMock(\Magento\Sales\Model\Order::class);

        $this->assertEquals('succesfull call', $instance->startTransaction($order));
    }

    public function throwsAnExceptionWhenKlarnaFailsProvider()
    {
        return [
            ['klarnapaylater'],
            ['klarnasliceit'],
        ];
    }

    /**
     * @dataProvider throwsAnExceptionWhenKlarnaFailsProvider
     */
    public function testThrowsAnExceptionWhenKlarnaFails($method)
    {
        $helperMock = $this->getHelperMock();
        $helperMock->method('getMethodCode')->willReturn($method);

        /** @var Mollie $instance */
        $instance = $this->get(Mollie::class, [
            'directory' => $this->createMock(\Magento\Directory\Helper\Data::class),
            'mollieHelper' => $helperMock,
            'ordersApi' => $this->getOrderMock(),
        ]);

        $order = $this->createMock(\Magento\Sales\Model\Order::class);

        try {
            $instance->startTransaction($order);
        } catch (\Exception $exception) {
            $this->assertEquals('[test] Something went wrong', $exception->getMessage());
            return;
        }

        $this->fail('We expected an exception but got none');
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    private function getOrderMock()
    {
        $exception = new \Exception('[test] Something went wrong');

        $ordersApiMock = $this->createMock(\Mollie\Payment\Model\Client\Orders::class);
        $ordersApiMock->expects($this->once())
            ->method('startTransaction')
            ->willThrowException($exception);

        return $ordersApiMock;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    private function getHelperMock()
    {
        $helperMock = $this->createMock(\Mollie\Payment\Helper\General::class);
        $helperMock->method('getApiKey')->willReturn('test_ABCDEFGHIJKLMNOPQRSTUVWXYZABCD');
        $helperMock->method('getApiMethod')->willReturn('order');

        return $helperMock;
    }
}