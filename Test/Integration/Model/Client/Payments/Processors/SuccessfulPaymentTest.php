<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Integration\Model\Client\Payments\Processors;

use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Mollie\Payment\Model\Client\Payments\Processors\SuccessfulPayment;
use Mollie\Payment\Model\Client\ProcessTransactionResponse;
use Mollie\Payment\Service\Mollie\Order\IsPaymentAlreadyProcessed;
use Mollie\Payment\Test\Integration\IntegrationTestCase;
use Mollie\Payment\Test\Integration\MolliePaymentBuilder;

class SuccessfulPaymentTest extends IntegrationTestCase
{
    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testCanceledOrderGetsUncanceled(): void
    {
        $order = $this->loadOrder('100000001');
        $order->setBaseCurrencyCode('EUR');
        $order->setMollieTransactionId('abc123');

        $items = $order->getItems();
        foreach ($items as $item) {
            if (!$item->getSku()) {
                $item->setSku($item->getProduct()->getSku());
            }
        }

        $item = array_shift($items);
        $item->setCurrencyCode('EUR');

        $this->objectManager->get(OrderRepositoryInterface::class)->save($order);
        $order->cancel();

        $this->assertEquals(Order::STATE_CANCELED, $order->getState());

        /** @var MolliePaymentBuilder $paymentBuilder */
        $paymentBuilder = $this->objectManager->create(MolliePaymentBuilder::class);
        $paymentBuilder->setAmount((float)$order->getBaseGrandTotal(), (float)$order->getBaseCurrencyCode());

        /** @var SuccessfulPayment $instance */
        $instance = $this->objectManager->create(SuccessfulPayment::class);
        $instance->process(
            $order,
            $paymentBuilder->build(),
            'webhook',
            $this->objectManager->create(ProcessTransactionResponse::class, [
                'success' => true,
                'status' => 'paid',
                'order_id' => $order->getIncrementId(),
                'type' => 'webhook',
            ]),
        );

        $freshOrder = $this->objectManager->get(OrderInterface::class)->load($order->getId(), 'entity_id');

        // There is a difference in ~2.3.4 and later, that's why we check both statuses as it is change somewhere in
        // those versions.
        $this->assertTrue(in_array(
            $freshOrder->getState(),
            [
                Order::STATE_PROCESSING,
                Order::STATE_COMPLETE,
            ],
        ), sprintf(
            'We expect the order status to be "processing" or "complete". Instead we got %s',
            $freshOrder->getState(),
        ));
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/invoice.php
     */
    public function testItMarksThePaymentAsProcessed(): void
    {
        $order = $this->prepareOrder();

        $this->processWebhook($order);

        $processedOrder = $this->loadOrder('100000001');

        $this->assertTrue(
            $processedOrder->getPayment()->getAdditionalInformation(IsPaymentAlreadyProcessed::PAYMENT_PROCESSED),
        );
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/invoice.php
     */
    public function testItIgnoresTheWebhookThatMollieSendsWhenTheRefundSettles(): void
    {
        $this->processWebhook($this->prepareOrder());

        $refundedOrder = $this->loadOrder('100000001');
        $refundedOrder->setState(Order::STATE_CLOSED);
        $refundedOrder->setStatus(Order::STATE_CLOSED);
        $this->removeTheStatusFlagThatOlderOrdersDoNotHave($refundedOrder);
        $this->objectManager->get(OrderRepositoryInterface::class)->save($refundedOrder);

        $this->processWebhook($this->loadOrder('100000001'));

        $freshOrder = $this->loadOrder('100000001');

        $this->assertEquals(Order::STATE_CLOSED, $freshOrder->getState());
        $this->assertEquals(Order::STATE_CLOSED, $freshOrder->getStatus());
    }

    private function removeTheStatusFlagThatOlderOrdersDoNotHave(OrderInterface $order): void
    {
        $order->getPayment()->unsAdditionalInformation(IsPaymentAlreadyProcessed::STATUS_UPDATED);
    }

    private function prepareOrder(): OrderInterface
    {
        $order = $this->loadOrder('100000001');
        $order->setMollieTransactionId('tr_ImaFDdVSCr');

        $this->objectManager->get(OrderRepositoryInterface::class)->save($order);

        return $this->loadOrder('100000001');
    }

    private function processWebhook(OrderInterface $order): void
    {
        /** @var MolliePaymentBuilder $paymentBuilder */
        $paymentBuilder = $this->objectManager->create(MolliePaymentBuilder::class);
        $paymentBuilder->setAmount((float)$order->getBaseGrandTotal(), $order->getBaseCurrencyCode());
        $paymentBuilder->setStatus('paid');
        $paymentBuilder->setMethod('ideal');

        /** @var SuccessfulPayment $instance */
        $instance = $this->objectManager->create(SuccessfulPayment::class);
        $instance->process(
            $order,
            $paymentBuilder->build(),
            'webhook',
            $this->objectManager->create(ProcessTransactionResponse::class, [
                'success' => true,
                'status' => 'paid',
                'order_id' => $order->getIncrementId(),
                'type' => 'webhook',
            ]),
        );
    }
}
