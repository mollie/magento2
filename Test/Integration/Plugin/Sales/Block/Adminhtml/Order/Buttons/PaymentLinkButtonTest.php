<?php
/**
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Plugin\Sales\Block\Adminhtml\Order\Buttons;

use Magento\Sales\Model\Order;
use Mollie\Payment\Plugin\Sales\Block\Adminhtml\Order\Buttons\PaymentLinkButton;
use Mollie\Payment\Test\Integration\IntegrationTestCase;
use Magento\Sales\Block\Adminhtml\Order\View as Subject;

class PaymentLinkButtonTest extends IntegrationTestCase
{
    /**
     * @magentoConfigFixture current_store payment/mollie_methods_paymentlink/allow_mark_as_paid 0
     */
    public function testDoesNotShowsTheButtonWhenDisabled()
    {
        $orderMock = $this->createMock(Order::class);

        $subjectMock = $this->createMock(Subject::class);
        $subjectMock->method('getOrder')->willReturn($orderMock);

        $subjectMock->expects($this->never())->method('addButton');

        /** @var PaymentLinkButton $instance */
        $instance = $this->objectManager->create(PaymentLinkButton::class);
        $instance->add($subjectMock);
    }

    /**
     * @magentoConfigFixture current_store payment/mollie_methods_paymentlink/allow_mark_as_paid 1
     */
    public function testDoesNotShowsTheButtonWhenWeCantCancel()
    {
        $orderMock = $this->createMock(Order::class);
        $orderMock->method('canCancel')->willReturn(false);

        $subjectMock = $this->createMock(Subject::class);
        $subjectMock->method('getOrder')->willReturn($orderMock);

        $subjectMock->expects($this->never())->method('addButton');

        /** @var PaymentLinkButton $instance */
        $instance = $this->objectManager->create(PaymentLinkButton::class);
        $instance->add($subjectMock);
    }

    /**
     * @magentoConfigFixture current_store payment/mollie_methods_paymentlink/allow_mark_as_paid 1
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testDoesNotShowsTheButtonWhenNotPaymentLink()
    {
        $order = $this->loadOrderById('100000001');
        $order->getPayment()->setMethod('checkmo');

        $subjectMock = $this->createMock(Subject::class);
        $subjectMock->method('getOrder')->willReturn($order);

        $subjectMock->expects($this->never())->method('addButton');

        /** @var PaymentLinkButton $instance */
        $instance = $this->objectManager->create(PaymentLinkButton::class);
        $instance->add($subjectMock);
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     * @magentoConfigFixture current_store payment/mollie_methods_paymentlink/allow_mark_as_paid 0
     */
    public function testDoesNotShowsTheButtonWhenMollieReorderIsNotAvailable()
    {
        $order = $this->loadOrderById('100000001');
        $order->getPayment()->setMethod('mollie_methods_paymentlink');

        $subjectMock = $this->createMock(Subject::class);
        $subjectMock->method('getOrder')->willReturn($order);

        $subjectMock->expects($this->never())->method('addButton');

        /** @var PaymentLinkButton $instance */
        $instance = $this->objectManager->create(PaymentLinkButton::class);
        $instance->add($subjectMock);
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     * @magentoConfigFixture default_store payment/mollie_methods_paymentlink/allow_mark_as_paid 1
     */
    public function testShowTheButtonWhenApplicable()
    {
        $order = $this->loadOrderById('100000001');
        $order->getPayment()->setMethod('mollie_methods_paymentlink');

        $subjectMock = $this->createMock(Subject::class);
        $subjectMock->method('getOrder')->willReturn($order);

        $subjectMock->expects($this->once())->method('addButton');

        /** @var PaymentLinkButton $instance */
        $instance = $this->objectManager->create(PaymentLinkButton::class);
        $instance->add($subjectMock);
    }
}
