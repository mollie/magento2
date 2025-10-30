<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Integration\Plugin\Sales\Block\Adminhtml\Order\Buttons;

use Magento\Sales\Block\Adminhtml\Order\View as Subject;
use Magento\Sales\Helper\Reorder;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Reorder\UnavailableProductsProvider;
use Mollie\Payment\Plugin\Sales\Block\Adminhtml\Order\Buttons\MarkAsPaidButton;
use Mollie\Payment\Test\Integration\IntegrationTestCase;

class MarkAsPaidButtonTest extends IntegrationTestCase
{
    /**
     * @magentoConfigFixture current_store payment/mollie_methods_paymentlink/allow_mark_as_paid 0
     */
    public function testDoesNotShowsTheButtonWhenDisabled(): void
    {
        $orderMock = $this->createMock(Order::class);

        $subjectMock = $this->createMock(Subject::class);
        $subjectMock->method('getOrder')->willReturn($orderMock);

        $subjectMock->expects($this->never())->method('addButton');

        /** @var MarkAsPaidButton $instance */
        $instance = $this->objectManager->create(MarkAsPaidButton::class);
        $instance->add($subjectMock);
    }

    /**
     * @magentoConfigFixture current_store payment/mollie_methods_paymentlink/allow_mark_as_paid 1
     */
    public function testDoesNotShowsTheButtonWhenWeCantCancel(): void
    {
        $orderMock = $this->createMock(Order::class);
        $orderMock->method('canCancel')->willReturn(false);

        $unavailableProductsProviderMock = $this->createMock(UnavailableProductsProvider::class);
        $unavailableProductsProviderMock->method('getForOrder')->willReturn(['product_sku_1']);

        $subjectMock = $this->createMock(Subject::class);
        $subjectMock->method('getOrder')->willReturn($orderMock);

        $reorderHelperMock = $this->createMock(Reorder::class);
        $reorderHelperMock->method('canReorder')->willReturn(true);

        $subjectMock->expects($this->never())->method('addButton');

        /** @var MarkAsPaidButton $instance */
        $instance = $this->objectManager->create(MarkAsPaidButton::class, [
            'reorderHelper' => $reorderHelperMock,
            'unavailableProductsProvider' => $unavailableProductsProviderMock,
        ]);
        $instance->add($subjectMock);
    }

    /**
     * @magentoConfigFixture current_store payment/mollie_methods_paymentlink/allow_mark_as_paid 1
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testDoesNotShowsTheButtonWhenNotPaymentLink(): void
    {
        $order = $this->loadOrderById('100000001');
        $order->getPayment()->setMethod('checkmo');

        $subjectMock = $this->createMock(Subject::class);
        $subjectMock->method('getOrder')->willReturn($order);

        $subjectMock->expects($this->never())->method('addButton');

        /** @var MarkAsPaidButton $instance */
        $instance = $this->objectManager->create(MarkAsPaidButton::class);
        $instance->add($subjectMock);
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     * @magentoConfigFixture current_store payment/mollie_methods_paymentlink/allow_mark_as_paid 0
     */
    public function testDoesNotShowsTheButtonWhenMollieReorderIsNotAvailable(): void
    {
        $order = $this->loadOrderById('100000001');
        $order->getPayment()->setMethod('mollie_methods_paymentlink');

        $subjectMock = $this->createMock(Subject::class);
        $subjectMock->method('getOrder')->willReturn($order);

        $subjectMock->expects($this->never())->method('addButton');

        /** @var MarkAsPaidButton $instance */
        $instance = $this->objectManager->create(MarkAsPaidButton::class);
        $instance->add($subjectMock);
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     * @magentoConfigFixture default_store payment/mollie_methods_paymentlink/allow_mark_as_paid 1
     */
    public function testShowTheButtonWhenApplicable(): void
    {
        $order = $this->loadOrderById('100000001');
        $order->getPayment()->setMethod('mollie_methods_paymentlink');

        $subjectMock = $this->createMock(Subject::class);
        $subjectMock->method('getOrder')->willReturn($order);

        $subjectMock->expects($this->once())->method('addButton');

        /** @var MarkAsPaidButton $instance */
        $instance = $this->objectManager->create(MarkAsPaidButton::class);
        $instance->add($subjectMock);
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     * @magentoConfigFixture current_store payment/mollie_methods_paymentlink/allow_mark_as_paid 1
     */
    public function testDoesNotShowWhenTheReorderHelperDisallowsReordering(): void
    {
        $reorderHelperMock = $this->createMock(Reorder::class);
        $reorderHelperMock->method('canReorder')->willReturn(false);

        $order = $this->loadOrderById('100000001');
        $order->getPayment()->setMethod('mollie_methods_paymentlink');

        $subjectMock = $this->createMock(Subject::class);
        $subjectMock->method('getOrder')->willReturn($order);
        $subjectMock->expects($this->never())->method('addButton');

        /** @var MarkAsPaidButton $instance */
        $instance = $this->objectManager->create(MarkAsPaidButton::class, [
            'reorderHelper' => $reorderHelperMock,
        ]);
        $instance->add($subjectMock);
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     * @magentoConfigFixture current_store payment/mollie_methods_paymentlink/allow_mark_as_paid 1
     */
    public function testDoesNotShowTheButtonWhenProductsAreUnavailable(): void
    {
        $unavailableProductsProviderMock = $this->createMock(UnavailableProductsProvider::class);
        $unavailableProductsProviderMock->method('getForOrder')->willReturn(['product_sku_1']);

        $reorderHelperMock = $this->createMock(Reorder::class);
        $reorderHelperMock->method('canReorder')->willReturn(true);

        $order = $this->loadOrderById('100000001');
        $order->getPayment()->setMethod('mollie_methods_paymentlink');

        $subjectMock = $this->createMock(Subject::class);
        $subjectMock->method('getOrder')->willReturn($order);
        $subjectMock->expects($this->never())->method('addButton');

        /** @var MarkAsPaidButton $instance */
        $instance = $this->objectManager->create(MarkAsPaidButton::class, [
            'reorderHelper' => $reorderHelperMock,
            'unavailableProductsProvider' => $unavailableProductsProviderMock,
        ]);
        $instance->add($subjectMock);
    }
}
