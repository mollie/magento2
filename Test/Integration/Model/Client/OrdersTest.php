<?php

namespace Mollie\Payment\Model\Client;

use Magento\Framework\Phrase;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\TestFramework\ObjectManager;
use Mollie\Api\Endpoints\OrderEndpoint;
use Mollie\Api\MollieApiClient;
use Mollie\Api\Types\OrderStatus;
use Mollie\Payment\Service\Order\OrderCommentHistory;
use Mollie\Payment\Test\Integration\TestCase;

class OrdersTest extends TestCase
{
    public function processTransactionProvider()
    {
        return [
            [
                'USD',
                OrderStatus::STATUS_PAID,
                [
                    'Mollie: Order Amount %1, Captures Amount %2',
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
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Mollie\Api\Exceptions\ApiException
     */
    public function testProcessTransaction($currency, $mollieOrderStatus, $orderCommentHistoryMessages)
    {
        $orderEndpointMock = $this->createMock(OrderEndpoint::class);
        $orderEndpointMock->method('get')->willReturn($this->mollieOrderMock($mollieOrderStatus, $currency));

        $mollieApiMock = $this->createMock(MollieApiClient::class);
        $mollieApiMock->orders = $orderEndpointMock;

        $orderLinesMock = $this->createMock(\Mollie\Payment\Model\OrderLines::class);

        $orderSenderMock = $this->createMock(\Magento\Sales\Model\Order\Email\Sender\OrderSender::class);
        $orderSenderMock->method('send')->willReturn(true);

        $invoiceSenderMock = $this->createMock(\Magento\Sales\Model\Order\Email\Sender\InvoiceSender::class);
        $invoiceSenderMock->method('send')->willReturn(true);

        $orderCommentHistoryMock = $this->createMock(OrderCommentHistory::class);
        foreach ($orderCommentHistoryMessages as $index => $currentMessage) {
            $orderCommentHistoryMock
                ->expects($this->at($index))
                ->method('add')
                ->with(
                    $this->isInstanceOf(OrderInterface::class),
                    $this->callback(function (Phrase $message) use ($currentMessage) {
                        $messageText = $message->getText();
                        $expectedText = __($currentMessage)->getText();

                        if ($messageText != $expectedText) {
                            $this->fail('We expected "' . $messageText . '" but got "' . $expectedText . '"');
                        }

                        return $messageText == $expectedText;
                    })
                );
        }

        /** @var Orders $instance */
        $instance = $this->objectManager->create(Orders::class, [
            'orderLines' => $orderLinesMock,
            'orderSender' => $orderSenderMock,
            'invoiceSender' => $invoiceSenderMock,
            'orderCommentHistory' => $orderCommentHistoryMock,
        ]);

        $order = $this->loadOrder('100000001');
        $order->setBaseCurrencyCode($currency);
        $order->setOrderCurrencyCode($currency);

        if ($mollieOrderStatus == OrderStatus::STATUS_PAID) {
            $order->setEmailSent(1);
        }

        $instance->processTransaction($order, $mollieApiMock);
    }

    /**
     * @return \Mollie\Api\Resources\Order
     */
    protected function mollieOrderMock($status, $currency)
    {
        $mollieOrder = new \Mollie\Api\Resources\Order($this->createMock(MollieApiClient::class));
        $mollieOrder->status = $status;
        $mollieOrder->amountCaptured = new \stdClass;
        $mollieOrder->amountCaptured->value = 50;
        $mollieOrder->amountCaptured->currency = 'EUR';

        $mollieOrder->amount = new \stdClass();
        $mollieOrder->amount->value = 100;
        $mollieOrder->amount->currency = $currency;

        $mollieOrder->_embedded = new \stdClass;
        $mollieOrder->_embedded->payments = [new \stdClass];
        $mollieOrder->_embedded->payments[0]->status = 'success';
        return $mollieOrder;
    }
}
