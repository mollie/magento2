<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Integration\Model\Client\Payments\Processors;

use Magento\Sales\Api\Data\CreditmemoCommentInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderStatusHistoryInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Mollie\Payment\Model\Client\Payments\Processors\HandleExternalRefunds;
use Mollie\Payment\Model\Client\ProcessTransactionResponse;
use Mollie\Payment\Test\Fakes\Api\Resources\PaymentWithRefundsFake;
use Mollie\Payment\Test\Integration\IntegrationTestCase;

class HandleExternalRefundsTest extends IntegrationTestCase
{
    /**
     * @magentoDataFixture Magento/Sales/_files/invoice.php
     */
    public function testCreatesACreditmemoForAnExternalRefundByDefault(): void
    {
        $order = $this->loadPreparedOrder();
        $payment = (new PaymentWithRefundsFake())->withRefund('re_external_123', 10);

        $this->processRefunds($order, $payment);

        $freshOrder = $this->loadOrder($order->getIncrementId());
        $this->assertSame(1, $freshOrder->getCreditmemosCollection()->getSize());
        $this->assertSame([], array_filter(
            $this->getCommentsMentioning($freshOrder, 're_external_123'),
            fn (OrderStatusHistoryInterface $history): bool => str_contains(
                (string) $history->getComment(),
                'No credit memo was created',
            ),
        ));
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/invoice.php
     */
    public function testCreatesACreditmemoForEachExternalRefundInTheSameCall(): void
    {
        $order = $this->loadPreparedOrder();
        $payment = (new PaymentWithRefundsFake())
            ->withRefund('re_external_123', 10)
            ->withRefund('re_external_456', 5);

        $this->processRefunds($order, $payment);

        $this->assertCreditmemoBaseGrandTotals($order, [5.0, 10.0]);
        foreach ($this->loadOrder($order->getIncrementId())->getCreditmemosCollection() as $creditmemo) {
            $comments = array_map(
                fn (CreditmemoCommentInterface $comment): string => (string) $comment->getComment(),
                array_values($creditmemo->getCommentsCollection()->getItems()),
            );
            $this->assertContains(
                'This refund was created outside Magento, possible through the Mollie Dashboard.',
                $comments,
            );
        }
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/invoice.php
     */
    public function testDoesNotCreateTheCreditmemosAgainWhenThePaymentIsProcessedAgain(): void
    {
        $order = $this->loadPreparedOrder();
        $payment = (new PaymentWithRefundsFake())
            ->withRefund('re_external_123', 10)
            ->withRefund('re_external_456', 5);

        $this->processRefunds($order, $payment);
        $this->processRefunds($this->loadOrder($order->getIncrementId()), $payment);

        $this->assertCreditmemoBaseGrandTotals($order, [5.0, 10.0]);
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/invoice.php
     * @magentoConfigFixture current_store payment/mollie_general/create_creditmemo_for_external_refunds 0
     */
    public function testAddsAnOrderCommentInsteadOfACreditmemoWhenDisabled(): void
    {
        $order = $this->loadPreparedOrder();
        $payment = (new PaymentWithRefundsFake())->withRefund('re_external_123', 10);

        $this->processRefunds($order, $payment);

        $freshOrder = $this->loadOrder($order->getIncrementId());
        $this->assertSame(0, $freshOrder->getCreditmemosCollection()->getSize());
        $this->assertCount(1, $this->getCommentsMentioning($freshOrder, 're_external_123'));
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/invoice.php
     * @magentoConfigFixture current_store payment/mollie_general/create_creditmemo_for_external_refunds 0
     */
    public function testAddsTheCommentOnlyOnceWhenThePaymentIsProcessedAgain(): void
    {
        $order = $this->loadPreparedOrder();
        $payment = (new PaymentWithRefundsFake())->withRefund('re_external_123', 10);

        $this->processRefunds($order, $payment);
        $this->processRefunds($order, $payment);

        $freshOrder = $this->loadOrder($order->getIncrementId());
        $this->assertCount(1, $this->getCommentsMentioning($freshOrder, 're_external_123'));
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/invoice.php
     * @magentoConfigFixture current_store payment/mollie_general/create_creditmemo_for_external_refunds 0
     */
    public function testAddsACommentForEachNewRefund(): void
    {
        $order = $this->loadPreparedOrder();
        $firstPayment = (new PaymentWithRefundsFake())->withRefund('re_external_123', 10);
        $secondPayment = (new PaymentWithRefundsFake())
            ->withRefund('re_external_123', 10)
            ->withRefund('re_external_456', 5);

        $this->processRefunds($order, $firstPayment);
        $this->processRefunds($order, $secondPayment);

        $freshOrder = $this->loadOrder($order->getIncrementId());
        $this->assertCount(1, $this->getCommentsMentioning($freshOrder, 're_external_123'));
        $this->assertCount(1, $this->getCommentsMentioning($freshOrder, 're_external_456'));
    }

    private function loadPreparedOrder(): OrderInterface
    {
        $order = $this->loadOrder('100000001');
        $order->setBaseCurrencyCode('EUR');
        $order->setOrderCurrencyCode('EUR');
        $order->setMollieTransactionId('tr_external_refunds');

        $this->objectManager->get(OrderRepositoryInterface::class)->save($order);

        return $order;
    }

    private function processRefunds(OrderInterface $order, PaymentWithRefundsFake $payment): void
    {
        $this->objectManager->create(HandleExternalRefunds::class)->process(
            $order,
            $payment,
            'webhook',
            $this->objectManager->create(ProcessTransactionResponse::class, [
                'success' => true,
                'status' => 'paid',
                'order_id' => $order->getIncrementId(),
                'type' => 'webhook',
            ]),
        );
    }

    /**
     * @param float[] $expected
     */
    private function assertCreditmemoBaseGrandTotals(OrderInterface $order, array $expected): void
    {
        $freshOrder = $this->loadOrder($order->getIncrementId());
        $grandTotals = array_map(
            'floatval',
            $freshOrder->getCreditmemosCollection()->getColumnValues('base_grand_total'),
        );
        sort($grandTotals);

        $this->assertSame($expected, $grandTotals);
    }

    /**
     * @return OrderStatusHistoryInterface[]
     */
    private function getCommentsMentioning(OrderInterface $order, string $refundId): array
    {
        return array_values(array_filter(
            $order->getStatusHistories() ?? [],
            fn (OrderStatusHistoryInterface $history): bool => str_contains((string) $history->getComment(), $refundId),
        ));
    }
}
