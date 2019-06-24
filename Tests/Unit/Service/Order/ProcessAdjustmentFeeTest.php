<?php

namespace Mollie\Payment\Service\Order;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Sales\Api\Data\CreditmemoInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Mollie\Api\Endpoints\OrderEndpoint;
use Mollie\Api\Endpoints\PaymentEndpoint;
use Mollie\Api\MollieApiClient;
use Mollie\Api\Resources\Order as MollieOrder;
use Mollie\Api\Resources\Payment;
use Mollie\Api\Resources\PaymentFactory;
use Mollie\Payment\Helper\General;
use PHPUnit\Framework\TestCase;

class ProcessAdjustmentFeeTest extends TestCase
{
    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * @var ProcessAdjustmentFee
     */
    private $instance;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $paymentEndpoint;

    protected function setUp()
    {
        parent::setUp();

        $mockBuilder = $this->getMockBuilder(PaymentFactory::class);
        $mockBuilder->disableOriginalConstructor();
        $mockBuilder->setMethods(['create']);
        $paymentFactory = $mockBuilder->getMock();
        $paymentFactory->method('create')->willReturn($this->createMock(Payment::class));

        $this->objectManager = new ObjectManager($this);
        $this->instance = $this->objectManager->getObject(ProcessAdjustmentFee::class, [
            'paymentFactory' => $paymentFactory,
            'mollieHelper' => $this->objectManager->getObject(General::class)
        ]);
    }

    public function testRefundsPositive()
    {
        $creditmemo = $this->createMock(CreditmemoInterface::class);
        $creditmemo->method('getAdjustment')->willReturn(123);

        $mollieApi = $this->buildMollieApiMock();

        $this->paymentEndpoint->expects($this->once())->method('refund')->with(
            $this->isInstanceOf(Payment::class), // $payment
            $this->callback(function ($argument) {
                $this->assertSame('123.00', $argument['amount']['value']);
                return true;
            }) // $data
        );

        $this->instance->handle($mollieApi, $this->getOrderMock(), $creditmemo);
    }

    public function testRefundsNegative()
    {
        $creditmemo = $this->createMock(CreditmemoInterface::class);
        $creditmemo->method('getAdjustmentNegative')->willReturn(-123);

        $mollieApi = $this->buildMollieApiMock();

        $this->paymentEndpoint->expects($this->once())->method('refund')->with(
            $this->isInstanceOf(Payment::class),
            $this->callback(function ($argument) {
                $this->assertSame('77.00', $argument['amount']['value']);
                return true;
            })
        );

        $this->instance->handle($mollieApi, $this->getOrderMock(), $creditmemo);
    }

    public function doNotRefundInMollieProvider()
    {
        return [
            [123, null, false],
            [null, -123, true],
        ];
    }

    /**
     * @dataProvider doNotRefundInMollieProvider
     */
    public function testDoNotRefundInMollie($getAdjustment, $getAdjustmentNegative, $expected)
    {
        $creditmemo = $this->createMock(CreditmemoInterface::class);
        $creditmemo->method('getAdjustment')->willReturn($getAdjustment);
        $creditmemo->method('getAdjustmentNegative')->willReturn($getAdjustmentNegative);

        $mollieApi = $this->buildMollieApiMock();

        $this->paymentEndpoint->method('refund');

        $this->instance->handle($mollieApi, $this->getOrderMock(), $creditmemo);

        $this->assertSame($expected, $this->instance->doNotRefundInMollie());
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
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

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    private function getOrderMock()
    {
        $order = $this->getMockBuilder(OrderInterface::class)
            ->setMethods(['getMollieTransactionId', 'getOrderCurrencyCode'])
            ->getMockForAbstractClass();
        $order->method('getOrderCurrencyCode')->willReturn('EUR');
        $order->method('getGrandTotal')->willReturn(200);

        return $order;
    }
}
