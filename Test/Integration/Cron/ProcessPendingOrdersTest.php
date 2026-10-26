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
use Mollie\Api\Types\PaymentStatus;
use Mollie\Payment\Cron\ProcessPendingOrders;
use Mollie\Payment\Model\Client\ProcessTransactionResponse;
use Mollie\Payment\Model\Methods\Paymentlink;
use Mollie\Payment\Model\Mollie;
use Mollie\Payment\Test\Fakes\ConfigFake;
use Mollie\Payment\Test\Integration\IntegrationTestCase;

class ProcessPendingOrdersTest extends IntegrationTestCase
{
    private ?ConfigFake $config = null;

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

    /**
     * @magentoConfigFixture default_store payment/mollie_general/enable_pending_order_cron 1
     * @magentoConfigFixture default_store payment/mollie_methods_paymentlink/days_before_expire 28
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testRecoversPaidPaymentLinkOrdersWhileTheLinkIsStillValid(): void
    {
        $order = $this->preparePaymentLinkOrder();

        $processedOrderIds = $this->runCron();

        $this->assertContains(
            (int) $order->getEntityId(),
            $processedOrderIds,
            'Paid payment link orders with a missed webhook must still be recovered while the link is valid.',
        );
    }

    /**
     * @magentoConfigFixture default_store payment/mollie_general/enable_pending_order_cron 1
     * @magentoConfigFixture default_store payment/mollie_methods_paymentlink/days_before_expire 28
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testDoesNotLogAnErrorForAnExpiredPaymentWhileThePaymentLinkIsStillValid(): void
    {
        $this->preparePaymentLinkOrder();

        $this->runCron(PaymentStatus::EXPIRED);

        $this->assertSame([], $this->getLoggedErrors());
    }

    /**
     * @magentoConfigFixture default_store payment/mollie_general/enable_pending_order_cron 1
     * @magentoConfigFixture default_store payment/mollie_methods_paymentlink/days_before_expire 1
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testLogsAnErrorForAnExpiredPaymentWhenThePaymentLinkIsExpired(): void
    {
        $this->preparePaymentLinkOrder();

        $this->runCron(PaymentStatus::EXPIRED);

        $this->assertCount(1, $this->getLoggedErrors());
    }

    private function preparePaymentLinkOrder(): OrderInterface
    {
        $order = $this->prepareOrder(Order::STATE_PENDING_PAYMENT);
        $order->getPayment()->setMethod(Paymentlink::CODE);

        return $this->objectManager->get(OrderRepositoryInterface::class)->save($order);
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
    private function runCron(string $status = PaymentStatus::PAID): array
    {
        $processedOrderIds = [];

        $mollieFake = new class($processedOrderIds, $status) extends Mollie {
            public function __construct(private array &$processedOrderIds, private string $status)
            {
            }

            public function processTransaction($orderId, string $type = 'webhook'): ProcessTransactionResponse
            {
                $this->processedOrderIds[] = (int) $orderId;

                return new ProcessTransactionResponse(
                    $this->status === PaymentStatus::PAID,
                    $this->status,
                    (string) $orderId,
                    $type,
                );
            }
        };

        $this->config = $this->objectManager->create(ConfigFake::class);

        /** @var ProcessPendingOrders $cron */
        $cron = $this->objectManager->create(ProcessPendingOrders::class, [
            'mollieModel' => $mollieFake,
            'config' => $this->config,
        ]);
        $cron->execute();

        return $processedOrderIds;
    }

    private function getLoggedErrors(): array
    {
        return array_values(array_filter(
            $this->config->getLoggedMessages(),
            fn (array $message): bool => $message['type'] === 'error',
        ));
    }
}
