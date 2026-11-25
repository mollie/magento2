<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Integration\Model\Client\Payments\Processors;

use Magento\Sales\Model\Order;
use Mollie\Api\Resources\Payment;
use Mollie\Payment\Model\Client\Payments\Processors\SendEmailForAsyncPayment;
use Mollie\Payment\Model\Client\ProcessTransactionResponse;
use Mollie\Payment\Test\Integration\IntegrationTestCase;
use Mollie\Payment\Test\Integration\MolliePaymentBuilder;
use PHPUnit\Framework\Attributes\DataProvider;

class SendEmailForAsyncPaymentTest extends IntegrationTestCase
{
    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testSetsOrderToPendingPaymentForAsyncMethod(): void
    {
        $order = $this->loadOrderById('100000001');
        $order->getPayment()->setMethod('mollie_methods_paybybank');

        /** @var SendEmailForAsyncPayment $instance */
        $instance = $this->objectManager->get(SendEmailForAsyncPayment::class);
        $response = $instance->process(
            $order,
            $this->getMolliePayment('paybybank', 'pending'),
            'webhook',
            $this->createResponse(),
        );

        $this->assertEquals(Order::STATE_PENDING_PAYMENT, $order->getState());
        $this->assertEquals('pending_payment', $order->getStatus());
        $this->assertEquals('pending', $response->getStatus());
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testSkipsNonAsyncMethod(): void
    {
        $order = $this->loadOrderById('100000001');
        $originalState = $order->getState();
        $inputResponse = $this->createResponse();

        /** @var SendEmailForAsyncPayment $instance */
        $instance = $this->objectManager->get(SendEmailForAsyncPayment::class);
        $response = $instance->process(
            $order,
            $this->getMolliePayment('ideal', 'open'),
            'webhook',
            $inputResponse,
        );

        $this->assertSame($inputResponse, $response);
        $this->assertEquals($originalState, $order->getState());
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     * @magentoConfigFixture default/sales_email/general/async_sending 0
     * @dataProvider unfinishedStatusProvider
     */
    #[DataProvider('unfinishedStatusProvider')]
    public function testDoesNotSendTheOrderConfirmationForUnpaidPayByBankPayment(string $status): void
    {
        $order = $this->loadOrderById('100000001');
        $order->getPayment()->setMethod('mollie_methods_paybybank');

        /** @var SendEmailForAsyncPayment $instance */
        $instance = $this->objectManager->get(SendEmailForAsyncPayment::class);
        $instance->process($order, $this->getMolliePayment('paybybank', $status), 'webhook', $this->createResponse());

        $this->assertNull($order->getEmailSent());
        $this->assertEquals(Order::STATE_PENDING_PAYMENT, $order->getState());
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     * @magentoConfigFixture default/sales_email/general/async_sending 0
     */
    public function testSendsTheOrderConfirmationForOpenBanktransferPayment(): void
    {
        $order = $this->loadOrderById('100000001');
        $order->getPayment()->setMethod('mollie_methods_banktransfer');

        /** @var SendEmailForAsyncPayment $instance */
        $instance = $this->objectManager->get(SendEmailForAsyncPayment::class);
        $instance->process($order, $this->getMolliePayment('banktransfer', 'open'), 'webhook', $this->createResponse());

        $this->assertEquals(1, $order->getEmailSent());
        $this->assertEquals(Order::STATE_PENDING_PAYMENT, $order->getState());
    }

    /**
     * @return array<string, array{string}>
     */
    public static function unfinishedStatusProvider(): array
    {
        return [
            'open' => ['open'],
            'pending' => ['pending'],
        ];
    }

    private function getMolliePayment(string $method, string $status): Payment
    {
        /** @var MolliePaymentBuilder $builder */
        $builder = $this->objectManager->create(MolliePaymentBuilder::class);
        $builder->setAmount(100, 'EUR');
        $builder->setMethod($method);
        $builder->setStatus($status);

        return $builder->build();
    }

    private function createResponse(): ProcessTransactionResponse
    {
        return $this->objectManager->create(ProcessTransactionResponse::class, [
            'success' => true,
            'status' => 'webhook',
            'order_id' => '-01',
            'type' => 'webhook',
        ]);
    }
}
