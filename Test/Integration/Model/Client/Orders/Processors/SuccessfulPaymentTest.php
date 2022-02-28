<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Model\Client\Orders\Processors;

use Magento\Sales\Api\Data\InvoiceInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order;
use Mollie\Api\Types\OrderStatus;
use Mollie\Payment\Test\Integration\IntegrationTestCase;
use Mollie\Payment\Test\Integration\MollieOrderBuilder;

class SuccessfulPaymentTest extends IntegrationTestCase
{
    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testReturnsErrorWhenTheCurrenciesDontMatch(): void
    {
        $order = $this->loadOrder('100000001');

        /** @var MollieOrderBuilder $orderBuilder */
        $orderBuilder = $this->objectManager->create(MollieOrderBuilder::class);
        $orderBuilder->setAmount(100, 'USD');

        /** @var SuccessfulPayment $instance */
        $instance = $this->objectManager->create(SuccessfulPayment::class);

        $result = $instance->process(
            $order,
            $orderBuilder->build(),
            'webhook',
            $this->createResponse(false)
        );

        $this->assertFalse($result->isSuccess());
        $this->assertEquals('paid', $result->getStatus());
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testTheTransactionIdIsSetOnThePayment()
    {
        $order = $this->loadOrder('100000001');

        /** @var MollieOrderBuilder $orderBuilder */
        $orderBuilder = $this->objectManager->create(MollieOrderBuilder::class);
        $orderBuilder->setAmount(100);
        $orderBuilder->addPayment('payment_001');
        $orderBuilder->setStatus(OrderStatus::STATUS_CANCELED);

        /** @var SuccessfulPayment $instance */
        $instance = $this->objectManager->create(SuccessfulPayment::class);

        $instance->process(
            $order,
            $orderBuilder->build(),
            'webhook',
            $this->createResponse(false)
        );

        $payment = $order->getPayment();

        $this->assertEquals('payment_001', $payment->getTransactionId());
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testACaptureNotificationIsRegistered()
    {
        $order = $this->loadOrder('100000001');

        $this->assertNull($order->getTotalPaid());

        /** @var MollieOrderBuilder $orderBuilder */
        $orderBuilder = $this->objectManager->create(MollieOrderBuilder::class);
        $orderBuilder->setAmount(100);
        $orderBuilder->addPayment('payment_001');
        $orderBuilder->setStatus(OrderStatus::STATUS_PAID);

        /** @var SuccessfulPayment $instance */
        $instance = $this->objectManager->create(SuccessfulPayment::class);

        $instance->process(
            $order,
            $orderBuilder->build(),
            'webhook',
            $this->createResponse(false)
        );

        $this->assertEquals(100, $order->getTotalPaid());
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testGeneratesInvoice()
    {
        $order = $this->loadOrder('100000001');

        $this->assertNull($order->getPayment()->getCreatedInvoice());

        /** @var MollieOrderBuilder $orderBuilder */
        $orderBuilder = $this->objectManager->create(MollieOrderBuilder::class);
        $orderBuilder->setAmount(100);
        $orderBuilder->addPayment('payment_001');
        $orderBuilder->setStatus(OrderStatus::STATUS_PAID);

        /** @var SuccessfulPayment $instance */
        $instance = $this->objectManager->create(SuccessfulPayment::class);

        $instance->process(
            $order,
            $orderBuilder->build(),
            'webhook',
            $this->createResponse(false)
        );

        $this->assertInstanceOf(InvoiceInterface::class, $order->getPayment()->getCreatedInvoice());
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testGeneratesInvoiceAndSendsEmail()
    {
        $order = $this->loadOrder('100000001');

        $this->assertNull($order->getPayment()->getCreatedInvoice());

        /** @var MollieOrderBuilder $orderBuilder */
        $orderBuilder = $this->objectManager->create(MollieOrderBuilder::class);
        $orderBuilder->setAmount(100);
        $orderBuilder->addPayment('payment_001');
        $orderBuilder->setStatus(OrderStatus::STATUS_PAID);

        /** @var SuccessfulPayment $instance */
        $instance = $this->objectManager->create(SuccessfulPayment::class);

        $instance->process(
            $order,
            $orderBuilder->build(),
            'webhook',
            $this->createResponse(false)
        );

        $invoice = $order->getPayment()->getCreatedInvoice();
        $this->assertInstanceOf(InvoiceInterface::class, $invoice);
        $this->assertTrue($invoice->getEmailSent(), 'The invoice email should be sent');
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testSendsConfirmationEmail()
    {
        $order = $this->loadOrder('100000001');

        $this->assertNull($order->getEmailSent());

        /** @var MollieOrderBuilder $orderBuilder */
        $orderBuilder = $this->objectManager->create(MollieOrderBuilder::class);
        $orderBuilder->setAmount(100);
        $orderBuilder->addPayment('payment_001');
        $orderBuilder->setStatus(OrderStatus::STATUS_PAID);

        /** @var SuccessfulPayment $instance */
        $instance = $this->objectManager->create(SuccessfulPayment::class);

        $instance->process(
            $order,
            $orderBuilder->build(),
            'webhook',
            $this->createResponse(false)
        );

        $this->assertTrue($order->getEmailSent());
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testCanceledOrderGetsUncanceled(): void
    {
        $order = $this->loadOrder('100000001');
        $order->cancel();

        $this->assertEquals(Order::STATE_CANCELED, $order->getState());

        /** @var MollieOrderBuilder $orderBuilder */
        $orderBuilder = $this->objectManager->create(MollieOrderBuilder::class);
        $orderBuilder->setAmount(100);
        $orderBuilder->addPayment('payment_001');
        $orderBuilder->setStatus(OrderStatus::STATUS_PAID);

        /** @var SuccessfulPayment $instance */
        $instance = $this->objectManager->create(SuccessfulPayment::class);

        $instance->process(
            $order,
            $orderBuilder->build(),
            'webhook',
            $this->createResponse(false)
        );

        $freshOrder = $this->objectManager->get(OrderInterface::class)->load($order->getId(), 'entity_id');

        // There is a difference in ~2.3.4 and later, that's why we check both statuses as it is change somewhere in
        // those versions.
        $this->assertTrue(in_array(
            $freshOrder->getState(),
            [
                Order::STATE_PROCESSING,
                Order::STATE_COMPLETE,
            ]
        ), 'We expect the order status to be "processing" or "complete".');
    }

    private function createResponse(
        bool $succes,
        string $status = 'paid',
        string $type = 'webhook',
        string $orderId = '100000001'
    ) {
        return $this->objectManager->create(\Mollie\Payment\Model\Client\ProcessTransactionResponse::class, [
            'success' => $succes,
            'status' => $status,
            'order_id' => $orderId,
            'type' => $type
        ]);
    }
}
