<?php

namespace Mollie\Payment\Service\Order;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Sales\Api\Data\CreditmemoInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Mollie\Api\MollieApiClient;
use Mollie\Payment\Helper\General;
use Mollie\Payment\Service\Mollie\Order\RefundUsingPayment;
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

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    private $refundUsingPaymentMock;

    protected function setUp()
    {
        parent::setUp();

        $this->objectManager = new ObjectManager($this);

        $this->refundUsingPaymentMock = $this->createMock(RefundUsingPayment::class);

        $this->instance = $this->objectManager->getObject(ProcessAdjustmentFee::class, [
            'mollieHelper' => $this->objectManager->getObject(General::class),
            'refundUsingPayment' => $this->refundUsingPaymentMock,
        ]);
    }

    public function testRefundsPositive()
    {
        $creditmemo = $this->createMock(CreditmemoInterface::class);
        $creditmemo->method('getAdjustment')->willReturn(123);

        $this->refundUsingPaymentMock->expects($this->once())->method('execute')->with(
            $this->isInstanceOf(MollieApiClient::class),
            999,
            'EUR',
            123
        );

        $this->instance->handle($this->createmock(MollieApiClient::class), $this->getOrderMock(), $creditmemo);
    }

    public function testRefundsNegative()
    {
        $creditmemo = $this->createMock(CreditmemoInterface::class);
        $creditmemo->method('getAdjustmentNegative')->willReturn(-123);

        $this->refundUsingPaymentMock->expects($this->once())->method('execute')->with(
            $this->isInstanceOf(MollieApiClient::class),
            999,
            'EUR',
            -123
        );

        $this->instance->handle($this->createmock(MollieApiClient::class), $this->getOrderMock(), $creditmemo);
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

        $this->refundUsingPaymentMock->method('execute');

        $this->instance->handle($this->createmock(MollieApiClient::class), $this->getOrderMock(), $creditmemo);

        $this->assertSame($expected, $this->instance->doNotRefundInMollie());
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
        $order->method('getMollieTransactionId')->willReturn(999);

        return $order;
    }
}
