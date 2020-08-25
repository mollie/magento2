<?php

namespace Mollie\Payment\Helper;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Sales\Api\OrderManagementInterface;
use Magento\Sales\Model\Order as OrderModel;
use Magento\Store\Model\Information;
use Magento\Store\Model\ScopeInterface;
use Mollie\Api\MollieApiClient;
use Mollie\Api\Resources\Order;
use Mollie\Payment\Helper\General as MollieHelper;
use PHPUnit\Framework\TestCase;

class GeneralTest extends TestCase
{
    /**
     * @var ObjectManager
     */
    private $objectManager;

    protected function setUp(): void
    {
        $this->objectManager = new ObjectManager($this);
    }

    public function returnsTheCorrectDescriptionProvider()
    {
        return [
            ['{ordernumber}', '0000025'],
            ['', '0000025'],
            ['{storename}', 'My Test Store'],
            ['{storename}: {ordernumber}', 'My Test Store: 0000025'],
            ['Order {ordernumber} from this store', 'Order 0000025 from this store'],
        ];
    }

    /**
     * @dataProvider returnsTheCorrectDescriptionProvider
     */
    public function testReturnsTheCorrectDescription($description, $expected)
    {
        $storeConfigMock = $this->createMock(ScopeConfigInterface::class);
        $storeConfigMock->method('getValue')
            ->withConsecutive(
                ['payment/mollie_methods_ideal/payment_description', ScopeInterface::SCOPE_STORE, 1],
                [Information::XML_PATH_STORE_INFO_NAME, ScopeInterface::SCOPE_STORE, 1]
            )
            ->willReturnOnConsecutiveCalls($description, 'My Test Store');

        /** @var MollieHelper $instance */
        $instance = $this->objectManager->getObject(MollieHelper::class);

        // The scopeConfig is burried in the context, use reflection to swap it with our mock
        $property = (new \ReflectionObject($instance))->getProperty('scopeConfig');
        $property->setAccessible(true);
        $property->setValue($instance, $storeConfigMock);

        $result = $instance->getPaymentDescription('ideal', '0000025', 1);

        $this->assertSame($expected, $result);
    }

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
