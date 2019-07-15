<?php

namespace Mollie\Payment\Model\Client;

use Magento\Framework\Phrase;
use Magento\Sales\Api\Data\OrderAddressInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Email\Sender\InvoiceSender;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Mollie\Api\Endpoints\OrderEndpoint;
use Mollie\Api\MollieApiClient;
use Mollie\Api\Types\OrderStatus;
use Mollie\Payment\Model\OrderLines;
use Mollie\Payment\Service\Order\OrderCommentHistory;
use Mollie\Payment\Test\Integration\TestCase;
use stdClass;

class OrdersTest extends TestCase
{
    /**
     * This key is invalid on purpose, as we can't work our way around the `new \Mollie\Api\MollieApiClient()` call.
     * It turns out that an invalid key also throws an exception, which is what we actually want in this case.
     *
     * @magentoConfigFixture default_store payment/mollie_general/apikey_test test_TEST_API_KEY
     * @magentoConfigFixture default_store payment/mollie_general/type test
     */
    public function testCancelOrderThrowsAnExceptionWithTheOrderIdIncluded()
    {
        /** @var Orders $instance */
        $instance = $this->objectManager->create(Orders::class);

        /** @var OrderInterface $order */
        $order = $this->objectManager->create(OrderInterface::class);
        $order->setEntityId(999);
        $order->setMollieTransactionId('MOLLIE-999');

        try {
            $instance->cancelOrder($order);
        } catch (\Magento\Framework\Exception\LocalizedException $exception) {
            $this->assertContains('Order ID: 999', $exception->getMessage());
            return;
        }

        $this->fail('We expected an exception but this was not thrown');
    }

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
     * @param $currency
     * @param $mollieOrderStatus
     * @param $orderCommentHistoryMessages
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

        $orderLinesMock = $this->createMock(OrderLines::class);

        $orderSenderMock = $this->createMock(OrderSender::class);
        $orderSenderMock->method('send')->willReturn(true);

        $invoiceSenderMock = $this->createMock(InvoiceSender::class);
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

        $this->assertEquals(Order::STATE_PROCESSING, $order->getState());
    }

    public function testRemovesEmptySpaceFromThePrefix()
    {
        /** @var Orders $instance */
        $instance = $this->objectManager->get(Orders::class);

        /** @var OrderAddressInterface $address */
        $address = $this->objectManager->get(OrderAddressInterface::class);

        $address->setPrefix('     ');

        $result = $instance->getAddressLine($address);

        $this->assertEmpty($result['title']);
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Mollie\Api\Exceptions\ApiException
     */
    public function testProcessTransactionHandlesAStackOfPaymentsCorrectly()
    {
        $mollieOrderMock = $this->mollieOrderMock('N/A', 'EUR');
        $mollieOrderMock->status = OrderStatus::STATUS_PAID;
        $mollieOrderMock->_embedded->payments = [];

        foreach (['cancelled', 'paid', 'expired'] as $status) {
            $payment = new stdClass;
            $payment->status = $status;

            $mollieOrderMock->_embedded->payments[] = $payment;
        }

        $orderEndpointMock = $this->createMock(OrderEndpoint::class);
        $orderEndpointMock->method('get')->willReturn($mollieOrderMock);

        $mollieApiMock = $this->createMock(MollieApiClient::class);
        $mollieApiMock->orders = $orderEndpointMock;

        $invoiceSenderMock = $this->createMock(InvoiceSender::class);
        $invoiceSenderMock->method('send')->willReturn(true);

        $orderLinesMock = $this->createMock(OrderLines::class);

        /** @var Orders $instance */
        $instance = $this->objectManager->create(Orders::class, [
            'invoiceSender' => $invoiceSenderMock,
            'orderLines' => $orderLinesMock,
        ]);

        $order = $this->loadOrder('100000001');
        $order->setEmailSent(1);
        $order->setBaseCurrencyCode('EUR');
        $order->setOrderCurrencyCode('EUR');

        $result = $instance->processTransaction($order, $mollieApiMock);

        $this->assertTrue($result['success']);
    }

    /**
     * @return \Mollie\Api\Resources\Order
     */
    protected function mollieOrderMock($status, $currency)
    {
        $mollieOrder = new \Mollie\Api\Resources\Order($this->createMock(MollieApiClient::class));
        $mollieOrder->status = $status;
        $mollieOrder->amountCaptured = new stdClass;
        $mollieOrder->amountCaptured->value = 50;
        $mollieOrder->amountCaptured->currency = 'EUR';

        $mollieOrder->amount = new stdClass();
        $mollieOrder->amount->value = 100;
        $mollieOrder->amount->currency = $currency;

        $mollieOrder->_embedded = new stdClass;
        $mollieOrder->_embedded->payments = [new stdClass];
        $mollieOrder->_embedded->payments[0]->status = 'success';

        return $mollieOrder;
    }
}
