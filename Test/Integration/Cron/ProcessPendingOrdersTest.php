<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Integration\Cron;

use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Mollie\Payment\Cron\ProcessPendingOrders;
use Mollie\Payment\Model\Client\ProcessTransactionResponse;
use Mollie\Payment\Model\Mollie;
use Mollie\Payment\Test\Integration\IntegrationTestCase;

class ProcessPendingOrdersTest extends IntegrationTestCase
{
    /**
     * @magentoConfigFixture default_store payment/mollie_general/enable_pending_order_cron 1
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testRecoversPendingPaymentOrders(): void
    {
        $order = $this->prepareOrder(Order::STATE_PENDING_PAYMENT);

        $processedOrderIds = $this->runCron();

        $this->assertContains(
            (int) $order->getEntityId(),
            $processedOrderIds,
            'The cron should pick up orders that are stuck in pending_payment.',
        );
    }

    /**
     * @magentoConfigFixture default_store payment/mollie_general/enable_pending_order_cron 1
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testRecoversPaymentReviewOrders(): void
    {
        $order = $this->prepareOrder(Order::STATE_PAYMENT_REVIEW);

        $processedOrderIds = $this->runCron();

        $this->assertContains(
            (int) $order->getEntityId(),
            $processedOrderIds,
            'Orders stuck in payment_review (manual capture awaiting a capture webhook) must also be recovered.',
        );
    }

    private function prepareOrder(string $state): OrderInterface
    {
        $order = $this->loadOrderById('100000001');
        $order->setState($state);
        $order->setStatus($order->getConfig()->getStateDefaultStatus($state));
        $order->setMollieTransactionId('tr_dummytransaction');
        $order->setCreatedAt(gmdate('Y-m-d H:i:s', time() - (2 * 86400)));

        return $this->objectManager->get(OrderRepositoryInterface::class)->save($order);
    }

    /**
     * @return int[]
     */
    private function runCron(): array
    {
        $processedOrderIds = [];

        $mollieFake = new class($processedOrderIds) extends Mollie {
            public function __construct(private array &$processedOrderIds)
            {
            }

            public function processTransaction($orderId, string $type = 'webhook'): ProcessTransactionResponse
            {
                $this->processedOrderIds[] = (int) $orderId;

                return new ProcessTransactionResponse(true, 'paid', (string) $orderId, $type);
            }
        };

        /** @var ProcessPendingOrders $cron */
        $cron = $this->objectManager->create(ProcessPendingOrders::class, [
            'mollieModel' => $mollieFake,
        ]);
        $cron->execute();

        return $processedOrderIds;
    }
}
