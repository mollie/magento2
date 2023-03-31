<?php

namespace Mollie\Payment\Test\Integration\Service\Order\Lines\Processor;

use Magento\Catalog\Model\Product\Type;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderItemInterface;
use Mollie\Payment\Service\Order\Lines\Processor\BundleWithoutDynamicPricing;
use Mollie\Payment\Test\Integration\IntegrationTestCase;

class BundleWithoutDynamicPricingTest extends IntegrationTestCase
{
    protected function setUpWithoutVoid()
    {
        if (getenv('CI')) {
            $this->markTestSkipped('Does not work on CI for several reasons');
        }
    }

    public function testSetsTheCorrectVatAmount(): void
    {
        $this->loadMagentoFixture('Magento/Bundle/_files/order_item_with_bundle_and_options.php');

        $order = $this->loadOrderById('100000001');

        /** @var BundleWithoutDynamicPricing $instance */
        $instance = $this->objectManager->create(BundleWithoutDynamicPricing::class);

        $bundleItem = $this->getBundleItem($order);
        $bundleItem->setDiscountAmount(10);
        $bundleItem->setBaseDiscountAmount(10);
        $bundleItem->setTaxPercent(21);

        $orderLine = [
            'totalAmount' => [
                'value' => 100,
                'currency' => 'EUR',
            ]
        ];

        $result = $instance->process($orderLine, $order, $bundleItem);

        // 100 - 10 = 90, 21% VAT = 15.62
        $this->assertEquals(15.62, $result['vatAmount']['value']);

        $this->loadMagentoFixture('Magento/Bundle/_files/order_item_with_bundle_and_options_rollback.php');
    }

    public function testUsesTheQuantity(): void
    {
        $this->loadMagentoFixture('Magento/Bundle/_files/order_item_with_bundle_and_options.php');

        $order = $this->loadOrderById('100000001');

        /** @var BundleWithoutDynamicPricing $instance */
        $instance = $this->objectManager->create(BundleWithoutDynamicPricing::class);

        $bundleItem = $this->getBundleItem($order);
        $bundleItem->setQtyOrdered(2);
        $bundleItem->setDiscountAmount(10);
        $bundleItem->setBaseDiscountAmount(10);
        $bundleItem->setTaxPercent(21);

        $orderLine = [
            'totalAmount' => [
                'value' => 100,
                'currency' => 'EUR',
            ]
        ];

        $result = $instance->process($orderLine, $order, $bundleItem);

        // 100 - 10 = 90, 21% VAT = 15.62
        $this->assertEquals(15.62, $result['vatAmount']['value']);
        $this->assertEquals(90, $result['totalAmount']['value']);

        $this->loadMagentoFixture('Magento/Bundle/_files/order_item_with_bundle_and_options_rollback.php');
    }

    public function getBundleItem(OrderInterface $order): OrderItemInterface
    {
        $items = $order->getItems();
        foreach ($items as $item) {
            if ($item->getProductType() == Type::TYPE_BUNDLE) {
                return $item;
            }
        }

        $this->fail('Expected to find a bundle item');
    }
}
