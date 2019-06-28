<?php

namespace Mollie\Payment\Service\Order\Lines;

use Magento\Sales\Api\Data\OrderInterface;
use Magento\TestFramework\ObjectManager;
use Mollie\Payment\Exceptions\NoStoreCreditFound;
use PHPUnit\Framework\TestCase;

class StoreCreditTest extends TestCase
{
    /**
     * @var \Magento\Framework\App\ObjectManager
     */
    private $objectManager;

    protected function setUp()
    {
        $this->objectManager = ObjectManager::getInstance();
    }

    public function orderHasStoreCreditProvider()
    {
        return [
            ['amstorecredit_amount'],
        ];
    }

    /**
     * @dataProvider orderHasStoreCreditProvider
     */
    public function testOrderHasStoreCreditReturnsTrueWhenApplicable($column)
    {
        /** @var OrderInterface $order */
        $order = $this->objectManager->get(OrderInterface::class);
        $order->setData($column, 20);

        /** @var StoreCredit $instance */
        $instance = $this->objectManager->get(StoreCredit::class);

        $this->assertTrue(
            $instance->orderHasStoreCredit($order),
            'The order has a store credit but the method can\'t find it'
        );
    }

    public function testOrderHasStoreCreditReturnsFalseWhenNotApplicable()
    {
        /** @var OrderInterface $order */
        $order = $this->objectManager->create(OrderInterface::class);

        /** @var StoreCredit $instance */
        $instance = $this->objectManager->get(StoreCredit::class);

        $this->assertFalse(
            $instance->orderHasStoreCredit($order),
            'The order doesn\'t have a store credit but the method thinks it does.'
        );
    }

    public function testThrowsAnExceptionWhenTheStoreCreditCantBeFound()
    {
        /** @var OrderInterface $order */
        $order = $this->objectManager->create(OrderInterface::class);
        $order->setEntityId(999);

        /** @var StoreCredit $instance */
        $instance = $this->objectManager->get(StoreCredit::class);

        try {
            $instance->getOrderLine($order, true);
        } catch (NoStoreCreditFound $exception) {
            $this->assertEquals(
                'We where unable to find the store credit for order #999',
                $exception->getMessage()
            );
            return;
        }

        $this->fail('We expected a ' . NoStoreCreditFound::class . ' exception but got none');
    }

    public function testCreatesTheOrderLine()
    {
        /** @var OrderInterface $order */
        $order = $this->objectManager->get(OrderInterface::class);
        $order->setBaseCurrencyCode('EUR');
        $order->setData('amstorecredit_amount', 20);
        $order->setData('amstorecredit_base_amount', 20);

        /** @var StoreCredit $instance */
        $instance = $this->objectManager->get(StoreCredit::class);
        $result = $instance->getOrderLine($order, true);

        $this->assertEquals('store_credit', $result['type']);
        $this->assertEquals(__('Store Credit'), $result['name']);
        $this->assertEquals(1, $result['quantity']);
        $this->assertEquals(['currency' => 'EUR', 'value' => '-20.00'], $result['unitPrice']);
        $this->assertEquals(['currency' => 'EUR', 'value' => '-20.00'], $result['totalAmount']);
        $this->assertEquals('0.00', $result['vatRate']);
        $this->assertEquals(['currency' => 'EUR', 'value' => '0.00'], $result['vatAmount']);
    }
}
