<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Integration\Service\Mollie\Order;

use Mollie\Payment\Model\Methods\Bancontact;
use Mollie\Payment\Model\Methods\Banktransfer;
use Mollie\Payment\Model\Methods\Billie;
use Mollie\Payment\Model\Methods\Ideal;
use Mollie\Payment\Model\Methods\In3;
use Mollie\Payment\Model\Methods\Riverty;
use Mollie\Payment\Service\Mollie\Order\CreateInvoiceOnShipment;
use Mollie\Payment\Test\Integration\IntegrationTestCase;

class CreateInvoiceOnShipmentTest extends IntegrationTestCase
{
    /**
     * @dataProvider isEnabledByMethodProvider
     * @magentoDataFixture Magento/Sales/_files/order.php
     * @magentoConfigFixture default_store payment/mollie_general/invoice_moment shipment
     * @param string $method
     * @return void
     */
    public function testIsEnabledByMethod(string $method): void
    {
        $order = $this->loadOrderById('100000001');
        $order->getPayment()->setMethod($method);
        $order->setMollieTransactionId('ord_1234567890');

        /** @var CreateInvoiceOnShipment $instance */
        $instance = $this->objectManager->create(CreateInvoiceOnShipment::class);

        $this->assertTrue($instance->execute($order));
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     * @magentoConfigFixture default_store payment/mollie_methods_creditcard/method payment
     * @magentoConfigFixture default_store payment/mollie_general/enable_manual_capture 1
     * @return void
     */
    public function testIsDisabledWhenCreatePaymentAuthorizationIsEnabledAndApiIsPayments(): void
    {
        $order = $this->loadOrderById('100000001');
        $methods = ['mollie_methods_applepay', 'mollie_methods_creditcard'];
        $order->getPayment()->setMethod($methods[array_rand($methods)]);
        $order->setMollieTransactionId('tr_1234567890');

        /** @var CreateInvoiceOnShipment $instance */
        $instance = $this->objectManager->create(CreateInvoiceOnShipment::class);

        $this->assertFalse($instance->execute($order));
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     * @magentoConfigFixture default_store payment/mollie_methods_creditcard/method order
     * @magentoConfigFixture default_store payment/mollie_general/enable_manual_capture 1
     * @return void
     */
    public function testIsDisabledWhenCreatePaymentAuthorizationIsEnabledAndApiIsOrders(): void
    {
        $order = $this->loadOrderById('100000001');
        $methods = ['mollie_methods_applepay', 'mollie_methods_creditcard'];
        $order->getPayment()->setMethod($methods[array_rand($methods)]);
        $order->setMollieTransactionId('ord_1234567890');

        /** @var CreateInvoiceOnShipment $instance */
        $instance = $this->objectManager->create(CreateInvoiceOnShipment::class);

        $this->assertFalse($instance->execute($order));
    }

    /**
     * @dataProvider isDisabledWhenTheMethodDoesNotSupportThisProvider
     * @magentoDataFixture Magento/Sales/_files/order.php
     * @magentoConfigFixture default_store payment/mollie_methods_creditcard/method order
     * @magentoConfigFixture default_store payment/mollie_general/enable_manual_capture 1
     * @return void
     */
    public function testIsDisabledWhenTheMethodDoesNotSupportThis(string $method): void
    {
        $order = $this->loadOrderById('100000001');
        $order->getPayment()->setMethod($method);
        $order->setMollieTransactionId('tr_1234567890');

        /** @var CreateInvoiceOnShipment $instance */
        $instance = $this->objectManager->create(CreateInvoiceOnShipment::class);

        $this->assertFalse($instance->execute($order));
    }

    public function isEnabledByMethodProvider(): array
    {
        return [
            [Billie::CODE],
            [In3::CODE],
            [Riverty::CODE],
        ];
    }

    public function isDisabledWhenTheMethodDoesNotSupportThisProvider(): array
    {
        return [
            [Bancontact::CODE],
            [Banktransfer::CODE],
            [Ideal::CODE],
        ];
    }
}
