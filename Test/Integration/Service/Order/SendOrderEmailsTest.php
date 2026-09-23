<?php

/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Integration\Service\Order;

use Magento\Framework\Exception\MailException;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderStatusHistoryInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Mollie\Payment\Service\Order\SendOrderEmails;
use Mollie\Payment\Test\Fakes\Model\Order\Email\OrderSenderFake;
use Mollie\Payment\Test\Integration\IntegrationTestCase;

class SendOrderEmailsTest extends IntegrationTestCase
{
    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     * @magentoConfigFixture default/sales_email/general/async_sending 1
     */
    public function testSchedulesTheOrderConfirmationWhenAsyncSendingIsEnabled(): void
    {
        $order = $this->loadOrder('100000001');

        $this->objectManager->create(SendOrderEmails::class)->sendOrderConfirmation($order);

        $reloadedOrder = $this->reloadOrder($order);
        $this->assertNull($reloadedOrder->getEmailSent());
        $this->assertEquals(1, $reloadedOrder->getSendEmail());
        $this->assertEquals(
            ['New order email scheduled for sending'],
            $this->getComments($order)
        );
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     * @magentoConfigFixture default/sales_email/general/async_sending 0
     */
    public function testSendsTheOrderConfirmationDirectlyWhenAsyncSendingIsDisabled(): void
    {
        $order = $this->loadOrder('100000001');

        $this->objectManager->create(SendOrderEmails::class)->sendOrderConfirmation($order);

        $this->assertEquals(1, $this->reloadOrder($order)->getEmailSent());
        $this->assertEquals(['New order email sent'], $this->getComments($order));
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     * @magentoConfigFixture default/sales_email/general/async_sending 0
     */
    public function testDoesNotReportTheOrderConfirmationAsSentWhenTheTransportFails(): void
    {
        $order = $this->loadOrder('100000001');
        $orderSender = $this->objectManager->create(OrderSenderFake::class);
        $orderSender->givenTransportFails();

        $this->createInstance($orderSender)->sendOrderConfirmation($order);

        $this->assertEquals(
            ['Unable to send the new order email, please check the logs for details'],
            $this->getComments($order)
        );
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testAddsTheExceptionMessageWhenTheOrderSenderThrows(): void
    {
        $order = $this->loadOrder('100000001');
        $orderSender = $this->objectManager->create(OrderSenderFake::class);
        $orderSender->givenSendThrows(new MailException(__('Broken pipe')));

        $this->createInstance($orderSender)->sendOrderConfirmation($order);

        $this->assertEquals(['Unable to send the new order email: Broken pipe'], $this->getComments($order));
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     * @magentoConfigFixture current_store sales_email/order/enabled 0
     */
    public function testAddsNoCommentWhenOrderConfirmationEmailsAreDisabled(): void
    {
        $order = $this->loadOrder('100000001');

        $this->objectManager->create(SendOrderEmails::class)->sendOrderConfirmation($order);

        $this->assertEquals([], $this->getComments($order));
    }

    private function createInstance(OrderSenderFake $orderSender): SendOrderEmails
    {
        return $this->objectManager->create(SendOrderEmails::class, ['orderSender' => $orderSender]);
    }

    /**
     * @return Order
     */
    private function reloadOrder(OrderInterface $order): OrderInterface
    {
        return $this->objectManager->create(OrderRepositoryInterface::class)->get($order->getEntityId());
    }

    /**
     * @return string[]
     */
    private function getComments(OrderInterface $order): array
    {
        return array_values(array_map(
            fn (OrderStatusHistoryInterface $history): string => (string) $history->getComment(),
            $this->reloadOrder($order)->getStatusHistories()
        ));
    }
}
