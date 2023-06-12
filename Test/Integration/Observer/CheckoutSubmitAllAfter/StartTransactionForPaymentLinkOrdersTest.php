<?php
/*
 *  Copyright Magmodules.eu. All rights reserved.
 *  See COPYING.txt for license details.
 */

namespace Mollie\Payment\Test\Integration\Observer\CheckoutSubmitAllAfter;

use Magento\Framework\Event\Observer;
use Mollie\Payment\Model\Mollie;
use Mollie\Payment\Observer\SalesModelServiceQuoteSubmitSuccess\StartTransactionForPaymentLinkOrders;
use Mollie\Payment\Test\Integration\IntegrationTestCase;

class StartTransactionForPaymentLinkOrdersTest extends IntegrationTestCase
{
    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testDoesNotStartTransactionForNonPaymentLinkOrders()
    {
        $mollieMock = $this->createMock(Mollie::class);
        $mollieMock->expects($this->never())->method('startTransaction');

        $order = $this->loadOrderById('100000001');
        $payment = $order->getPayment();
        $payment->setMethod('mollie_methods_ideal');

        $observer = $this->objectManager->create(Observer::class);
        $observer->setData('order', $order);

        /** @var StartTransactionForPaymentLinkOrders $instance */
        $instance = $this->objectManager->create(StartTransactionForPaymentLinkOrders::class, [
            'mollie' => $mollieMock,
        ]);

        $instance->execute($observer);
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testStartTransactionForPaymentLinkOrders()
    {
        $mollieMock = $this->createMock(Mollie::class);
        $mollieMock->expects($this->once())->method('startTransaction');

        $order = $this->loadOrderById('100000001');
        $payment = $order->getPayment();
        $payment->setMethod('mollie_methods_paymentlink');

        $observer = $this->objectManager->create(Observer::class);
        $observer->setData('order', $order);

        /** @var StartTransactionForPaymentLinkOrders $instance */
        $instance = $this->objectManager->create(StartTransactionForPaymentLinkOrders::class, [
            'mollie' => $mollieMock,
        ]);

        $instance->execute($observer);
    }
}
