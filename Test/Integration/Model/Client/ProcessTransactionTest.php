<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Mollie\Payment\Test\Integration\Model\Client;

use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderStatusHistoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Email\Sender\InvoiceSender;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Mollie\Api\Endpoints\OrderEndpoint;
use Mollie\Api\MollieApiClient;
use Mollie\Api\Types\OrderStatus;
use Mollie\Payment\Model\Client\Orders;
use Mollie\Payment\Model\OrderLines;
use Mollie\Payment\Test\Fakes\Model\Client\Orders\OrderProcessorsFake;
use Mollie\Payment\Test\Integration\IntegrationTestCase;
use PHPUnit\Framework\MockObject\MockObject;

class ProcessTransactionTest extends IntegrationTestCase
{
    public function processTransactionProvider(): array
    {
        return [
            [
                'USD',
                OrderStatus::STATUS_PAID,
                [
                    'Mollie: Order Amount USD 100, Captured Amount EUR 50',
                    'Notified customer about invoice #%1'
                ]
            ],
            [
                'EUR',
                OrderStatus::STATUS_PAID,
                [
                    'Notified customer about invoice #%1'
                ]
            ],
            [
                'EUR',
                OrderStatus::STATUS_AUTHORIZED,
                [
                    'New order email sent'
                ]
            ],
        ];
    }

    /**
     * @dataProvider processTransactionProvider
     * @magentoDataFixture Magento/Sales/_files/order.php
     *
     * @param $currency
     * @param $mollieOrderStatus
     * @param $orderCommentHistoryMessages
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Mollie\Api\Exceptions\ApiException
     */
    public function testProcessTransaction($currency, $mollieOrderStatus, $orderCommentHistoryMessages)
    {
        $orderLinesMock = $this->createMock(OrderLines::class);

        $orderSenderMock = $this->createMock(OrderSender::class);
        $orderSenderMock->method('send')->willReturn(true);

        $invoiceSenderMock = $this->createMock(InvoiceSender::class);
        $invoiceSenderMock->method('send')->willReturn(true);

        /** @var Orders\ProcessTransaction $instance */
        $instance = $this->objectManager->create(Orders\ProcessTransaction::class, [
            'orderLines' => $orderLinesMock,
            'orderSender' => $orderSenderMock,
            'invoiceSender' => $invoiceSenderMock,
            'mollieApiClient' => $this->mollieClientMock($mollieOrderStatus, $currency),
        ]);

        $order = $this->loadOrder('100000001');
        $order->setBaseCurrencyCode($currency);
        $order->setOrderCurrencyCode($currency);

        if ($mollieOrderStatus == OrderStatus::STATUS_PAID) {
            $order->setEmailSent(1);
        }

        $instance->execute($order);

        $freshOrder = $this->objectManager->get(OrderInterface::class)->load($order->getId(), 'entity_id');

        // Dump the comments in an array
        $messages = array_map( function (OrderStatusHistoryInterface $history) {
            return $history->getComment();
        }, $freshOrder->getStatusHistories());

        foreach ($orderCommentHistoryMessages as $message) {
            $parsedMessage = __($message, $freshOrder->getInvoiceCollection()->getLastItem()->getIncrementId());

            $this->assertTrue(
                in_array($parsedMessage, $messages),
                sprintf('We expected the message "%s" to be in the history, but it is missing', $parsedMessage)
            );
        }

        $this->assertEquals(Order::STATE_PROCESSING, $order->getState());
    }

    public function cancelsTheOrderProvider(): array
    {
        return [
            [OrderStatus::STATUS_CANCELED],
            [OrderStatus::STATUS_EXPIRED],
        ];
    }

    /**
     * @dataProvider cancelsTheOrderProvider
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testCancelsTheOrder(string $state)
    {
        $apiClient = $this->mollieClientMock($state, 'EUR');

        /** @var Orders\ProcessTransaction $instance */
        $instance = $this->objectManager->create(Orders\ProcessTransaction::class, [
            'orderLines' => $this->createMock(OrderLines::class),
            'mollieApiClient' => $apiClient,
        ]);

        $order = $this->loadOrder('100000001');
        $this->assertNotEquals(Order::STATE_CANCELED, $order->getState());

        $instance->execute($order);

        $order = $this->loadOrder('100000001');
        $this->assertEquals(Order::STATE_CANCELED, $order->getState());
    }

    public function callsTheCorrectProcessorProvider(): array
    {
        return [
            [OrderStatus::STATUS_CREATED, 'created'],
        ];
    }

    /**
     * @dataProvider callsTheCorrectProcessorProvider
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testCallsTheCorrectProcessor(string $state, string $event)
    {
        $apiClient = $this->mollieClientMock($state, 'EUR');

        /** @var OrderProcessorsFake $orderProcessorsFake */
        $orderProcessorsFake = $this->objectManager->create(OrderProcessorsFake::class);
        $orderProcessorsFake->disableParentCall();

        /** @var Orders\ProcessTransaction $instance */
        $instance = $this->objectManager->create(Orders\ProcessTransaction::class, [
            'orderLines' => $this->createMock(OrderLines::class),
            'mollieApiClient' => $apiClient,
            'orderProcessors' => $orderProcessorsFake,
        ]);

        $order = $this->loadOrder('100000001');

        $instance->execute($order);

        $this->assertTrue(
            in_array($event, $orderProcessorsFake->getCalledEvents()),
            sprintf('The "%s" event is not called', $event)
        );
    }

    protected function mollieClientMock(string $status, string $currency): MockObject
    {
        $mollieOrder = new \Mollie\Api\Resources\Order($this->createMock(MollieApiClient::class));
        $mollieOrder->lines = [];
        $mollieOrder->status = $status;
        $mollieOrder->amountCaptured = new \stdClass();
        $mollieOrder->amountCaptured->value = 50;
        $mollieOrder->amountCaptured->currency = 'EUR';

        $mollieOrder->amount = new \stdClass();
        $mollieOrder->amount->value = 100;
        $mollieOrder->amount->currency = $currency;

        $mollieOrder->_embedded = new \stdClass;
        $mollieOrder->_embedded->payments = [new \stdClass];
        $mollieOrder->_embedded->payments[0]->id = 'tr_abc1234';
        $mollieOrder->_embedded->payments[0]->status = 'success';

        $orderEndpointMock = $this->createMock(OrderEndpoint::class);
        $orderEndpointMock->method('get')->willReturn($mollieOrder);

        $mollieApiMock = $this->createMock(MollieApiClient::class);
        $mollieApiMock->orders = $orderEndpointMock;

        $mollieClientMock = $this->createMock(\Mollie\Payment\Service\Mollie\MollieApiClient::class);
        $mollieClientMock->method('loadByStore')->willReturn($mollieApiMock);

        return $mollieClientMock;
    }
}
