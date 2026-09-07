<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Integration\Service\Order\Lines;

use Magento\Sales\Api\Data\OrderInterface;
use Mollie\Payment\Service\Order\Lines\PaymentFee;
use Mollie\Payment\Test\Integration\IntegrationTestCase;

class PaymentFeeTest extends IntegrationTestCase
{
    public function testOrderHasPaymentFee(): void
    {
        /** @var OrderInterface $order */
        $order = $this->objectManager->create(OrderInterface::class);

        $order->setData('mollie_payment_fee', 1);
        $order->setData('base_mollie_payment_fee', 1);

        /** @var PaymentFee $instance */
        $instance = $this->objectManager->create(PaymentFee::class);

        $this->assertTrue($instance->orderHasPaymentFee($order));
    }

    public function testGetOrderLine(): void
    {
        /** @var OrderInterface $order */
        $order = $this->objectManager->create(OrderInterface::class);
        $order->setBaseCurrencyCode('EUR');

        $order->setData('base_mollie_payment_fee', 1);
        $order->setData('base_mollie_payment_fee_tax', 0.21);

        /** @var PaymentFee $instance */
        $instance = $this->objectManager->create(PaymentFee::class);

        $line = $instance->getOrderLine($order, true);

        $this->assertEquals('surcharge', $line['type']);
        $this->assertEquals(__('Payment Fee'), $line['description']);
        $this->assertArrayNotHasKey('name', $line);
    }

    public function testGetOrderLineAcceptsStringValuesFromTheDatabase(): void
    {
        /** @var OrderInterface $order */
        $order = $this->objectManager->create(OrderInterface::class);
        $order->setBaseCurrencyCode('EUR');

        $order->setData('base_mollie_payment_fee', '1.6500');
        $order->setData('base_mollie_payment_fee_tax', '0.3500');

        /** @var PaymentFee $instance */
        $instance = $this->objectManager->create(PaymentFee::class);

        $line = $instance->getOrderLine($order, true);

        $this->assertEquals('2.00', $line['unitPrice']['value']);
        $this->assertEquals('2.00', $line['totalAmount']['value']);
        $this->assertEquals('0.35', $line['vatAmount']['value']);
        $this->assertEquals(21.21, $line['vatRate']);
    }
}
