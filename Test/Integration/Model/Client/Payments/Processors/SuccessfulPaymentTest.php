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
}
