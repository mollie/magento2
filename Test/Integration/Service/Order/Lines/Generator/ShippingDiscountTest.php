<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Integration\Service\Order\Lines\Generator;

use Magento\Sales\Api\Data\OrderInterface;
use Mollie\Payment\Service\Order\Lines\Generator\ShippingDiscount;
use Mollie\Payment\Test\Integration\IntegrationTestCase;

class ShippingDiscountTest extends IntegrationTestCase
{
    public function testDoesNothingWhenThereIsNoShippingDiscount(): void
    {
        /** @var OrderInterface $order */
        $order = $this->objectManager->create(OrderInterface::class);

        /** @var ShippingDiscount $instance */
        $instance = $this->objectManager->create(ShippingDiscount::class);

        $this->assertCount(0, $instance->process($order, []));
    }

    public function testBuildsADiscountLineFromStringValuesFromTheDatabase(): void
    {
        /** @var OrderInterface $order */
        $order = $this->objectManager->create(OrderInterface::class);
        $order->setBaseCurrencyCode('EUR');
        $order->setOrderCurrencyCode('EUR');
        $order->setData('shipping_discount_amount', '5.0000');
        $order->setData('base_shipping_discount_amount', '5.0000');

        /** @var ShippingDiscount $instance */
        $instance = $this->objectManager->create(ShippingDiscount::class);

        $result = $instance->process($order, []);

        $this->assertCount(1, $result);
        $line = end($result);
        $this->assertEquals('-5.00', $line['unitPrice']['value']);
        $this->assertEquals('-5.00', $line['totalAmount']['value']);
        $this->assertEquals('0.00', $line['vatAmount']['value']);
    }
}
