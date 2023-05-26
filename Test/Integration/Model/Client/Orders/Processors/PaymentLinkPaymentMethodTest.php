<?php

namespace Mollie\Payment\Test\Integration\Model\Client\Orders\Processors;

use Magento\Sales\Api\Data\OrderPaymentInterface;
use Mollie\Payment\Model\Client\Orders\Processors\PaymentLinkPaymentMethod;
use Mollie\Payment\Model\Client\ProcessTransactionResponse;
use Mollie\Payment\Test\Integration\IntegrationTestCase;
use Mollie\Payment\Test\Integration\MollieOrderBuilder;

class PaymentLinkPaymentMethodTest extends IntegrationTestCase
{
    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     * @return void
     */
    public function testWhenPaymentLinkUsedSavesTheMethod(): void
    {
        /** @var OrderPaymentInterface $payment */
        $payment = $this->objectManager->create(OrderPaymentInterface::class);
        $payment->setMethod('mollie_methods_paymentlink');

        $order = $this->loadOrderById('100000001');
        $order->setPayment($payment);

        /** @var MollieOrderBuilder $orderBuilder */
        $orderBuilder = $this->objectManager->create(MollieOrderBuilder::class);
        $orderBuilder->setMethod('ideal');

        /** @var PaymentLinkPaymentMethod $instance */
        $instance = $this->objectManager->get(PaymentLinkPaymentMethod::class);

        $instance->process(
            $order,
            $orderBuilder->build(),
            'webhook',
            $this->objectManager->create(ProcessTransactionResponse::class, [
                'success' => true,
                'status' => 'paid',
                'order_id' => '-01',
                'type' => 'webhook',
            ])
        );

        $this->assertEquals('ideal', $order->getPayment()->getAdditionalInformation('payment_link_method_used'));
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     * @return void
     */
    public function testDoesNotSaveWhenNotPaymentLinkUsed(): void
    {
        /** @var OrderPaymentInterface $payment */
        $payment = $this->objectManager->create(OrderPaymentInterface::class);
        $payment->setMethod('mollie_methods_ideal'); // Anything but paymentlink

        $order = $this->loadOrderById('100000001');
        $order->setPayment($payment);

        /** @var MollieOrderBuilder $orderBuilder */
        $orderBuilder = $this->objectManager->create(MollieOrderBuilder::class);
        $orderBuilder->setMethod('ideal');

        /** @var PaymentLinkPaymentMethod $instance */
        $instance = $this->objectManager->get(PaymentLinkPaymentMethod::class);

        $instance->process(
            $order,
            $orderBuilder->build(),
            'webhook',
            $this->objectManager->create(ProcessTransactionResponse::class, [
                'success' => true,
                'status' => 'paid',
                'order_id' => '-01',
                'type' => 'webhook',
            ])
        );

        $this->assertNull($order->getPayment()->getAdditionalInformation('payment_link_method_used'));
    }
}
