<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Test\Integration\Service\Order\Lines;

use Mollie\Payment\Service\Order\Lines\Processor\VoucherCategory;
use Mollie\Payment\Test\Integration\IntegrationTestCase;

class VoucherCategoryTest extends IntegrationTestCase
{
    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     * @magentoConfigFixture default_store payment/mollie_methods_voucher/category custom_attribute
     * @magentoConfigFixture default_store payment/mollie_methods_voucher/custom_attribute voucher_category
     */
    public function testHandlesCustomAttributeWithNoneValue()
    {
        $this->createAttribute();

        $order = $this->loadOrder('100000001');
        $order->getPayment()->setMethod('mollie_methods_voucher');

        $items = $order->getItems();
        $orderItem = array_shift($items);

        /** @var VoucherCategory $instance */
        $instance = $this->objectManager->create(VoucherCategory::class);

        $product = $orderItem->getProduct();
        $product->setData('voucher_category', 'none');

        $result = $instance->process([], $order, $orderItem);

        $this->assertArrayNotHasKey('category', $result);
    }

    private function createAttribute()
    {
        $eavSetup = $this->objectManager->create(\Magento\Eav\Setup\EavSetup::class);
        $eavSetup->addAttribute(
            \Magento\Catalog\Model\Product::ENTITY,
            'voucher_category',
            [
                'type'         => 'varchar',
                'label'        => 'Voucher Category',
                'input'        => 'text',
                'required'     => false,
                'visible'      => true,
                'user_defined' => true,
                'position'     => 999,
                'system'       => 0,
            ]
        );
    }
}