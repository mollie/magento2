<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Test\Integration\Model\Client\Processors;

use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Mollie\Api\MollieApiClient;
use Mollie\Api\Resources\Order;
use Mollie\Api\Types\OrderStatus;
use Mollie\Payment\Model\Client\Orders\Processors\SendConfirmationEmailForBanktransfer;
use Mollie\Payment\Test\Integration\IntegrationTestCase;

class SendConfirmationEmailForBanktransferTest extends IntegrationTestCase
{
    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testSendsTheConfirmationEmail()
    {
        $orderSendMock = $this->createMock(OrderSender::class);
        $orderSendMock->expects($this->once())->method('send');

        /** @var SendConfirmationEmailForBanktransfer $instance */
        $instance = $this->objectManager->create(SendConfirmationEmailForBanktransfer::class, [
            'orderSender' => $orderSendMock,
        ]);

        $order = $this->loadOrder('100000001');

        $mollieOrder = new Order(new MollieApiClient);
        $mollieOrder->status = OrderStatus::STATUS_CREATED;
        $mollieOrder->method = 'banktransfer';

        $instance->process($order, $mollieOrder, 'webhook', null);
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testDoesNothingWhenNotBanktransfer()
    {
        $orderSendMock = $this->createMock(OrderSender::class);
        $orderSendMock->expects($this->never())->method('send');

        /** @var SendConfirmationEmailForBanktransfer $instance */
        $instance = $this->objectManager->create(SendConfirmationEmailForBanktransfer::class, [
            'orderSender' => $orderSendMock,
        ]);

        $order = $this->loadOrder('100000001');

        $mollieOrder = new Order(new MollieApiClient);
        $mollieOrder->status = OrderStatus::STATUS_CREATED;
        $mollieOrder->method = 'ideal';

        $instance->process($order, $mollieOrder, 'webhook', null);
    }
}
