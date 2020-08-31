<?php

namespace Mollie\Payment\Test\Integration\Model\Client;

use Magento\Framework\Phrase;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Email\Sender\InvoiceSender;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Mollie\Api\Endpoints\PaymentEndpoint;
use Mollie\Api\MollieApiClient;
use Mollie\Api\Types\OrderStatus;
use Mollie\Api\Types\PaymentStatus;
use Mollie\Payment\Model\Client\Orders;
use Mollie\Payment\Model\Client\Payments;
use Mollie\Payment\Service\Order\OrderCommentHistory;
use Mollie\Payment\Test\Integration\IntegrationTestCase;

class PaymentsTest extends IntegrationTestCase
{
    public function processTransactionProvider()
    {
        return [
            [
                'USD',
                OrderStatus::STATUS_PAID,
                [
                    'Mollie: Captured %1, Settlement Amount %2',
                    'New order email sent',
                    'Notified customer about invoice #%1'
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
        $paymentEndpointMock = $this->createMock(PaymentEndpoint::class);
        $paymentEndpointMock->method('get')->willReturn($this->getMolliePayment($mollieOrderStatus, $currency));

        $mollieApiMock = $this->createMock(MollieApiClient::class);
        $mollieApiMock->payments = $paymentEndpointMock;

        $orderLinesMock = $this->createMock(\Mollie\Payment\Model\OrderLines::class);

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
        $instance = $this->objectManager->create(Payments::class, [
            'orderLines' => $orderLinesMock,
            'orderSender' => $orderSenderMock,
            'invoiceSender' => $invoiceSenderMock,
            'orderCommentHistory' => $orderCommentHistoryMock,
        ]);

        $order = $this->loadOrder('100000001');
        $order->setBaseCurrencyCode($currency);
        $order->setOrderCurrencyCode($currency);

        $instance->processTransaction($order, $mollieApiMock);

        $this->assertEquals(Order::STATE_PROCESSING, $order->getState());
    }

    /**
     * @return \Mollie\Api\Resources\Payment
     */
    protected function getMolliePayment($status, $currency)
    {
        $payment = new \Mollie\Api\Resources\Payment($this->createMock(MollieApiClient::class));
        $payment->status = $status;
        $payment->settlementAmount = new \stdClass;
        $payment->settlementAmount->value = 50;
        $payment->settlementAmount->currency = 'EUR';

        $payment->amount = new \stdClass();
        $payment->amount->value = 100;
        $payment->amount->currency = $currency;

        $payment->_embedded = new \stdClass;
        $payment->_embedded->payments = [new \stdClass];
        $payment->_embedded->payments[0]->status = 'success';

        return $payment;
    }

    public function checksIfTheOrderHasAnUpdateProvider()
    {
        return [
            [PaymentStatus::STATUS_OPEN, Order::STATE_NEW],
            [PaymentStatus::STATUS_PENDING, Order::STATE_PENDING_PAYMENT],
            [PaymentStatus::STATUS_AUTHORIZED, Order::STATE_PROCESSING],
            [PaymentStatus::STATUS_CANCELED, Order::STATE_CANCELED],
            [PaymentStatus::STATUS_EXPIRED, Order::STATE_CLOSED],
            [PaymentStatus::STATUS_PAID, Order::STATE_PROCESSING],
            [PaymentStatus::STATUS_FAILED, Order::STATE_CANCELED],
        ];
    }

    /**
     * @dataProvider checksIfTheOrderHasAnUpdateProvider
     */
    public function testChecksIfTheOrderHasAnUpdate($mollieStatus, $magentoStatus)
    {
        /** @var OrderInterface $order */
        $order = $this->objectManager->create(OrderInterface::class);

        $mollieApi = new MollieApiClient();
        $mollieOrder = new \Mollie\Api\Resources\Order($mollieApi);

        $paymentsApiMock = $this->createMock(PaymentEndpoint::class);
        $paymentsApiMock->method('get')->willReturn($mollieOrder);
        $mollieApi->payments = $paymentsApiMock;

        $mollieOrder->status = $mollieStatus;
        $order->setState($magentoStatus);

        /** @var Payments $instance */
        $instance = $this->objectManager->create(Payments::class);

        $this->assertFalse($instance->orderHasUpdate($order, $mollieApi));
    }
}
