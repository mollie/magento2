<?php

namespace Mollie\Payment\Helper;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;

class GeneralTest extends \PHPUnit\Framework\TestCase
{
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
        $ob = new ObjectManager($this);

        $storeConfigMock = $this->createMock(ScopeConfigInterface::class);
        $storeManagerMock = $this->createMock(StoreManagerInterface::class);
        $storeMock = $this->createMock(StoreInterface::class);

        $storeManagerMock->method('getStore')->willReturn($storeMock);
        $storeMock->method('getName')->willReturn('My Test Store');

        $storeConfigMock->method('getValue')->willReturn($description);

        /** @var \Mollie\Payment\Helper\General $instance */
        $instance = $ob->getObject(\Mollie\Payment\Helper\General::class, [
            'storeManager' => $storeManagerMock,
        ]);

        // The scopeConfig is burried in the context, use reflection to swap it with our mock
        $property = (new \ReflectionObject($instance))->getProperty('scopeConfig');
        $property->setAccessible(true);
        $property->setValue($instance, $storeConfigMock);

        $result = $instance->getPaymentDescription('ideal', '0000025');

        $this->assertSame($expected, $result);
    }
}
