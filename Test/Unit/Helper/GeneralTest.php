<?php

namespace Mollie\Payment\Helper;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use Mollie\Api\MollieApiClient;
use Mollie\Api\Resources\Order;
use Mollie\Payment\Helper\General as MollieHelper;

class GeneralTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ObjectManager
     */
    private $objectManager;

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

    protected function setUp()
    {
        $this->objectManager = new ObjectManager($this);
    }

    /**
     * @dataProvider returnsTheCorrectDescriptionProvider
     */
    public function testReturnsTheCorrectDescription($description, $expected)
    {
        $storeConfigMock = $this->createMock(ScopeConfigInterface::class);
        $storeManagerMock = $this->createMock(StoreManagerInterface::class);
        $storeMock = $this->createMock(StoreInterface::class);

        $storeManagerMock->method('getStore')->willReturn($storeMock);
        $storeMock->method('getName')->willReturn('My Test Store');

        $storeConfigMock->method('getValue')->willReturn($description);

        /** @var MollieHelper $instance */
        $instance = $this->objectManager->getObject(MollieHelper::class, [
            'storeManager' => $storeManagerMock,
        ]);

        // The scopeConfig is burried in the context, use reflection to swap it with our mock
        $property = (new \ReflectionObject($instance))->getProperty('scopeConfig');
        $property->setAccessible(true);
        $property->setValue($instance, $storeConfigMock);

        $result = $instance->getPaymentDescription('ideal', '0000025');

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
}
