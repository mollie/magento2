<?php

namespace Mollie\Payment\Test\Unit\Helper;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Sales\Api\OrderManagementInterface;
use Magento\Sales\Model\Order as OrderModel;
use Magento\Store\Model\Information;
use Magento\Store\Model\ScopeInterface;
use Mollie\Api\MollieApiClient;
use Mollie\Api\Resources\Order;
use Mollie\Payment\Helper\General;
use Mollie\Payment\Helper\General as MollieHelper;
use Mollie\Payment\Test\Unit\UnitTestCase;

class GeneralTest extends UnitTestCase
{
    public function getLastRelevantStatusProvider()
    {
        return [
            [['expired'], 'expired'],
            [['expired', 'paid'], 'paid'],
            [['paid', 'expired'], 'paid'],
            [['authorized', 'paid', 'expired'], 'paid'],
        ];
    }

    /**
     * @param $statuses
     * @param $expected
     * @dataProvider getLastRelevantStatusProvider
     */
    public function testGetLastRelevantStatus($statuses, $expected)
    {
        /** @var MollieHelper $instance */
        $instance = $this->objectManager->getObject(MollieHelper::class);

        $order = new Order($this->createMock(MollieApiClient::class));
        $order->_embedded = new \stdClass;
        $order->_embedded->payments = [];

        foreach ($statuses as $status) {
            $payment = new \stdClass;
            $payment->status = $status;
            $order->_embedded->payments[] = $payment;
        }

        $status = $instance->getLastRelevantStatus($order);

        $this->assertEquals($expected, $status);
    }

    public function testReturnsNullIfNoPaymentsAreAvailable()
    {
        /** @var MollieHelper $instance */
        $instance = $this->objectManager->getObject(MollieHelper::class);

        $order = new Order($this->createMock(MollieApiClient::class));

        $status = $instance->getLastRelevantStatus($order);

        $this->assertNull($status);
    }

    public function testRegisterCancellationReturnsFalseWhenAlreadyCanceled()
    {
        /** @var OrderModel $order */
        $order = $this->objectManager->getObject(OrderModel::class);
        $order->setId(999);
        $order->setState(OrderModel::STATE_CANCELED);

        /** @var General $instance */
        $instance = $this->objectManager->getObject(General::class);
        $result = $instance->registerCancellation($order, 'payment canceled');

        $this->assertFalse($result);
    }

    public function testRegisterCancellationCancelsTheOrder()
    {
        $orderManagementMock = $this->createMock(OrderManagementInterface::class);
        $orderManagementMock->expects($this->once())->method('cancel')->with(999);

        /** @var OrderModel $order */
        $order = $this->createPartialMock(OrderModel::class, ['cancel']);
        $order->setId(999);
        $order->setState(OrderModel::STATE_PROCESSING);

        $payment = $this->objectManager->getObject(OrderModel\Payment::class);
        $order->setPayment($payment);

        /** @var General $instance */
        $instance = $this->objectManager->getObject(General::class, [
            'orderManagement' => $orderManagementMock,
        ]);
        $result = $instance->registerCancellation($order, 'payment canceled');

        $this->assertTrue($result);
    }

    public function testRegisterCancellationSetsTheCorrectMessage()
    {
        /** @var OrderModel $order */
        $order = $this->objectManager->getObject(OrderModel::class);
        $order->setId(999);
        $order->setState(OrderModel::STATE_PROCESSING);

        $payment = $this->objectManager->getObject(OrderModel\Payment::class);
        $order->setPayment($payment);

        /** @var General $instance */
        $instance = $this->objectManager->getObject(General::class);
        $instance->registerCancellation($order, 'canceled');

        $this->assertEquals('The order was canceled, reason: payment canceled', $payment->getMessage()->render());
    }
}
