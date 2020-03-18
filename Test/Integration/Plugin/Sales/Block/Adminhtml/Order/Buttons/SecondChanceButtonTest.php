<?php
/**
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Plugin\Sales\Block\Adminhtml\Order\Buttons;

use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Block\Adminhtml\Order\View as Subject;
use Magento\Sales\Model\Order;
use Mollie\Payment\Config;
use Mollie\Payment\Test\Integration\IntegrationTestCase;

class SecondChanceButtonTest extends IntegrationTestCase
{
    public function testAddsTheButton()
    {
        $order = $this->objectManager->create(OrderInterface::class);
        $order->setState(Order::STATE_PENDING_PAYMENT);

        $configMock = $this->createMock(Config::class);
        $configMock->method('isSecondChanceEmailEnabled')->willReturn(true);

        $subjectMock = $this->createMock(Subject::class);
        $subjectMock->method('getOrder')->willReturn($order);
        $subjectMock->expects($this->once())->method('addButton')->with('mollie_payment_second_chance_email');

        /** @var SecondChanceButton $instance */
        $instance = $this->objectManager->create(SecondChanceButton::class, ['config' => $configMock]);
        $instance->add($subjectMock);
    }

    public function testDoesNothingWhenDisabled()
    {
        $order = $this->objectManager->create(OrderInterface::class);

        $configMock = $this->createMock(Config::class);
        $configMock->method('isSecondChanceEmailEnabled')->willReturn(false);

        $subjectMock = $this->createMock(Subject::class);
        $subjectMock->method('getOrder')->willReturn($order);
        $subjectMock->expects($this->never())->method('addButton')->with('mollie_payment_second_chance_email');

        /** @var SecondChanceButton $instance */
        $instance = $this->objectManager->create(SecondChanceButton::class, ['config' => $configMock]);
        $instance->add($subjectMock);
    }

    public function testIsNotVisibleWhenNotPendingPaymentState()
    {
        $order = $this->objectManager->create(OrderInterface::class);
        $order->setState(Order::STATE_CLOSED);

        $configMock = $this->createMock(Config::class);
        $configMock->method('isSecondChanceEmailEnabled')->willReturn(true);

        $subjectMock = $this->createMock(Subject::class);
        $subjectMock->method('getOrder')->willReturn($order);
        $subjectMock->expects($this->never())->method('addButton')->with('mollie_payment_second_chance_email');

        /** @var SecondChanceButton $instance */
        $instance = $this->objectManager->create(SecondChanceButton::class, ['config' => $configMock]);
        $instance->add($subjectMock);
    }
}
