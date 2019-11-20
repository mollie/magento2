<?php
/**
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Plugin\Sales\Block\Adminhtml\Order;

use Magento\Sales\Model\Order;
use Mollie\Payment\Test\Integration\IntegrationTestCase;
use Magento\Sales\Block\Adminhtml\Order\View as Subject;

class ViewTest extends IntegrationTestCase
{
    public function testDoesNotShowsTheButtonWhenWeCantCancel()
    {
        $orderMock = $this->createMock(Order::class);
        $orderMock->method('canCancel')->willReturn(false);

        $subjectMock = $this->createMock(Subject::class);
        $subjectMock->method('getOrder')->willReturn($orderMock);

        $subjectMock->expects($this->never())->method('addButton');

        /** @var View $instance */
        $instance = $this->objectManager->create(View::class);
        $instance->beforeSetLayout($subjectMock);
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testDoesNotShowsTheButtonWhenNotPaymentLink()
    {
        $order = $this->loadOrderById('100000001');
        $order->getPayment()->setMethod('checkmo');

        $subjectMock = $this->createMock(Subject::class);
        $subjectMock->method('getOrder')->willReturn($order);

        $subjectMock->expects($this->never())->method('addButton');

        /** @var View $instance */
        $instance = $this->objectManager->create(View::class);
        $instance->beforeSetLayout($subjectMock);
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     * @magentoConfigFixture current_store payment/checkmo/active 0
     */
    public function testDoesNotShowsTheButtonWhenCheckmoIsNotAvailable()
    {
        $order = $this->loadOrderById('100000001');
        $order->getPayment()->setMethod('mollie_methods_paymentlink');

        $subjectMock = $this->createMock(Subject::class);
        $subjectMock->method('getOrder')->willReturn($order);

        $subjectMock->expects($this->never())->method('addButton');

        /** @var View $instance */
        $instance = $this->objectManager->create(View::class);
        $instance->beforeSetLayout($subjectMock);
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     * @magentoConfigFixture current_store payment/checkmo/active 1
     */
    public function testShowTheButtonWhenApplicable()
    {
        $order = $this->loadOrderById('100000001');
        $order->getPayment()->setMethod('mollie_methods_paymentlink');

        $subjectMock = $this->createMock(Subject::class);
        $subjectMock->method('getOrder')->willReturn($order);

        $subjectMock->expects($this->once())->method('addButton');

        /** @var View $instance */
        $instance = $this->objectManager->create(View::class);
        $instance->beforeSetLayout($subjectMock);
    }
}
