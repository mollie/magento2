<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Integration\Service\Magento;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Sales\Api\Data\OrderStatusHistoryInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Mollie\Api\Fake\MockResponse;
use Mollie\Api\Http\Requests\GetPaymentRequest;
use Mollie\Api\Types\PaymentStatus;
use Mollie\Payment\Api\Data\PaymentTokenInterface;
use Mollie\Payment\Api\Data\TransactionToOrderInterface;
use Mollie\Payment\Api\PaymentTokenRepositoryInterface;
use Mollie\Payment\Api\TransactionToOrderRepositoryInterface;
use Mollie\Payment\Service\Magento\GetOrderIdsByTransactionId;
use Mollie\Payment\Service\Mollie\MollieApiClient;
use Mollie\Payment\Service\PaymentToken\PaymentTokenForOrder;
use Mollie\Payment\Test\Fakes\Service\Mollie\FakeMollieApiClient;
use Mollie\Payment\Test\Integration\IntegrationTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class GetOrderIdsByTransactionIdTest extends IntegrationTestCase
{
    private const FIXTURE_ORDER_INCREMENT_ID = '100000001';
    private const EXISTING_TRANSACTION_ID = 'tr_existingOnOrder';
    private const UNLINKED_TRANSACTION_ID = 'tr_unlinkedDuplicate';
    private const LEGACY_ORDER_TRANSACTION_ID = 'ord_legacyOrder';

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testReturnsOrderIdWhenTransactionToOrderRecordExists(): void
    {
        $order = $this->loadOrderById('100000001');

        /** @var TransactionToOrderInterface $model */
        $model = $this->objectManager->create(TransactionToOrderInterface::class);
        $model->setTransactionId('tr_existing123');
        $model->setOrderId((int) $order->getEntityId());
        $this->objectManager->get(TransactionToOrderRepositoryInterface::class)->save($model);

        /** @var GetOrderIdsByTransactionId $instance */
        $instance = $this->objectManager->create(GetOrderIdsByTransactionId::class);
        $result = $instance->execute('tr_existing123');

        $this->assertEquals([(int) $order->getEntityId()], $result);
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testMatchesByMetadataWhenNoTransactionToOrderRecordExists(): void
    {
        $order = $this->loadOrderById('100000001');
        $orderId = $order->getEntityId();

        $client = \Mollie\Api\MollieApiClient::fake([
            GetPaymentRequest::class => MockResponse::ok(
                '{"id":"tr_metadata123","status":"open","metadata":{"order_id":' . $orderId . '}}'
            ),
        ]);

        /** @var FakeMollieApiClient $fakeMollieApiClient */
        $fakeMollieApiClient = $this->objectManager->create(FakeMollieApiClient::class);
        $fakeMollieApiClient->setInstance($client);
        $this->objectManager->addSharedInstance($fakeMollieApiClient, MollieApiClient::class);

        /** @var GetOrderIdsByTransactionId $instance */
        $instance = $this->objectManager->create(GetOrderIdsByTransactionId::class);
        $result = $instance->execute('tr_metadata123');

        $this->assertEquals([$orderId], $result);
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testLinksTransactionToOrderWhenMatchedByMetadata(): void
    {
        $order = $this->loadOrderById('100000001');
        $orderId = $order->getEntityId();

        $client = \Mollie\Api\MollieApiClient::fake([
            GetPaymentRequest::class => MockResponse::ok(
                '{"id":"tr_link123","status":"open","metadata":{"order_id":' . $orderId . '}}'
            ),
        ]);

        /** @var FakeMollieApiClient $fakeMollieApiClient */
        $fakeMollieApiClient = $this->objectManager->create(FakeMollieApiClient::class);
        $fakeMollieApiClient->setInstance($client);
        $this->objectManager->addSharedInstance($fakeMollieApiClient, MollieApiClient::class);

        /** @var GetOrderIdsByTransactionId $instance */
        $instance = $this->objectManager->create(GetOrderIdsByTransactionId::class);
        $instance->execute('tr_link123');

        // Reload the order to verify the transaction ID was linked
        $reloadedOrder = $this->objectManager->get(OrderRepositoryInterface::class)
            ->get($orderId);

        $this->assertEquals('tr_link123', $reloadedOrder->getMollieTransactionId());
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testDoesNotOverwriteConcurrentlyUpdatedOrderWhenLinkingByMetadata(): void
    {
        $orderId = (int) $this->loadOrderById('100000001')->getEntityId();

        // The shared repository is the instance GetOrderIdsByTransactionId receives via DI.
        // Seed its identity map with a stale baseline: still pending, no confirmation email sent.
        $sharedRepository = $this->objectManager->get(OrderRepositoryInterface::class);
        $baseline = $sharedRepository->get($orderId);
        $baseline->setState(Order::STATE_PENDING_PAYMENT);
        $baseline->setStatus('pending_payment');
        $baseline->setEmailSent(0);
        $sharedRepository->save($baseline);

        // Simulate a concurrent queue consumer that promotes the order to processing and
        // marks the confirmation email as sent. A separate repository instance is used so the
        // shared identity map keeps holding the stale baseline, reproducing the race.
        $concurrentRepository = $this->objectManager->create(OrderRepositoryInterface::class);
        $concurrent = $concurrentRepository->get($orderId);
        $concurrent->setState(Order::STATE_PROCESSING);
        $concurrent->setStatus('processing');
        $concurrent->setEmailSent(1);
        $concurrentRepository->save($concurrent);

        $client = \Mollie\Api\MollieApiClient::fake([
            GetPaymentRequest::class => MockResponse::ok(
                '{"id":"tr_concurrent123","status":"open","metadata":{"order_id":' . $orderId . '}}'
            ),
        ]);

        /** @var FakeMollieApiClient $fakeMollieApiClient */
        $fakeMollieApiClient = $this->objectManager->create(FakeMollieApiClient::class);
        $fakeMollieApiClient->setInstance($client);
        $this->objectManager->addSharedInstance($fakeMollieApiClient, MollieApiClient::class);

        /** @var GetOrderIdsByTransactionId $instance */
        $instance = $this->objectManager->create(GetOrderIdsByTransactionId::class);
        $result = $instance->execute('tr_concurrent123');

        $this->assertEquals([$orderId], $result);

        $reloaded = $this->objectManager->create(OrderRepositoryInterface::class)->get($orderId);

        $this->assertEquals('tr_concurrent123', $reloaded->getMollieTransactionId());
        $this->assertEquals(
            Order::STATE_PROCESSING,
            $reloaded->getState(),
            'Linking the transaction must not revert a concurrently processed order'
        );
        $this->assertEquals(
            1,
            (int) $reloaded->getEmailSent(),
            'Linking the transaction must not clear a concurrently set email_sent flag'
        );
    }

    public function testReturnsEmptyArrayWhenNoMatchFound(): void
    {
        $client = \Mollie\Api\MollieApiClient::fake([
            GetPaymentRequest::class => MockResponse::ok(
                '{"id":"tr_nomatch123","status":"open","metadata":{}}'
            ),
        ]);

        /** @var FakeMollieApiClient $fakeMollieApiClient */
        $fakeMollieApiClient = $this->objectManager->create(FakeMollieApiClient::class);
        $fakeMollieApiClient->setInstance($client);
        $this->objectManager->addSharedInstance($fakeMollieApiClient, MollieApiClient::class);

        /** @var GetOrderIdsByTransactionId $instance */
        $instance = $this->objectManager->create(GetOrderIdsByTransactionId::class);
        $result = $instance->execute('tr_nomatch123');

        $this->assertEquals([], $result);
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testLinksAnExtraTransactionWhenTheOrderAlreadyHasOne(): void
    {
        $orderId = $this->prepareOrderWithTransaction(self::EXISTING_TRANSACTION_ID);
        $this->fakeUnlinkedPayment($orderId);

        $result = $this->objectManager->create(GetOrderIdsByTransactionId::class)
            ->execute(self::UNLINKED_TRANSACTION_ID);

        $this->assertEquals([$orderId], $result);
        $this->assertEqualsCanonicalizing(
            [self::EXISTING_TRANSACTION_ID, self::UNLINKED_TRANSACTION_ID],
            $this->getLinkedTransactionIds($orderId),
        );
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testKeepsTheTransactionIdOfTheOrderWhenLinkingAnExtraTransaction(): void
    {
        $orderId = $this->prepareOrderWithTransaction(self::EXISTING_TRANSACTION_ID);
        $this->fakeUnlinkedPayment($orderId);

        $this->objectManager->create(GetOrderIdsByTransactionId::class)->execute(self::UNLINKED_TRANSACTION_ID);

        $this->assertEquals(
            self::EXISTING_TRANSACTION_ID,
            $this->objectManager->create(OrderRepositoryInterface::class)->get($orderId)->getMollieTransactionId(),
        );
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testAddsACommentWhenLinkingAnExtraTransaction(): void
    {
        $orderId = $this->prepareOrderWithTransaction(self::EXISTING_TRANSACTION_ID);
        $this->fakeUnlinkedPayment($orderId);

        $this->objectManager->create(GetOrderIdsByTransactionId::class)->execute(self::UNLINKED_TRANSACTION_ID);

        $comments = array_map(
            fn (OrderStatusHistoryInterface $history): string => (string) $history->getComment(),
            $this->objectManager->create(OrderRepositoryInterface::class)->get($orderId)->getStatusHistories(),
        );
        $this->assertNotEmpty(array_filter(
            $comments,
            fn (string $comment): bool => str_contains($comment, self::UNLINKED_TRANSACTION_ID),
        ));
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testDoesNotLinkTheExistingTransactionTwice(): void
    {
        $orderId = $this->prepareOrderWithTransaction(self::EXISTING_TRANSACTION_ID);
        $this->linkTransaction(self::EXISTING_TRANSACTION_ID, $orderId);
        $this->fakeUnlinkedPayment($orderId);

        $this->objectManager->create(GetOrderIdsByTransactionId::class)->execute(self::UNLINKED_TRANSACTION_ID);

        $this->assertEqualsCanonicalizing(
            [self::EXISTING_TRANSACTION_ID, self::UNLINKED_TRANSACTION_ID],
            $this->getLinkedTransactionIds($orderId),
        );
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testLinksTheOwnTransactionOfTheOrderOnceWithoutAComment(): void
    {
        $orderId = $this->prepareOrderWithTransaction(self::UNLINKED_TRANSACTION_ID);
        $this->fakeUnlinkedPayment($orderId);
        $commentCount = count(
            $this->objectManager->create(OrderRepositoryInterface::class)->get($orderId)->getStatusHistories(),
        );

        $result = $this->objectManager->create(GetOrderIdsByTransactionId::class)
            ->execute(self::UNLINKED_TRANSACTION_ID);

        $this->assertEquals([$orderId], $result);
        $this->assertSame([self::UNLINKED_TRANSACTION_ID], $this->getLinkedTransactionIds($orderId));
        $this->assertCount(
            $commentCount,
            $this->objectManager->create(OrderRepositoryInterface::class)->get($orderId)->getStatusHistories(),
        );
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testDoesNotLinkAnExtraTransactionToALegacyOrdersApiOrder(): void
    {
        $orderId = $this->prepareOrderWithTransaction(self::LEGACY_ORDER_TRANSACTION_ID);
        $this->fakeUnlinkedPayment($orderId);

        $result = $this->objectManager->create(GetOrderIdsByTransactionId::class)
            ->execute(self::UNLINKED_TRANSACTION_ID);

        $this->assertSame([], $result);
        $this->assertSame([], $this->getLinkedTransactionIds($orderId));
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     * @dataProvider finalUnsuccessfulStatusDataProvider
     */
    #[DataProvider('finalUnsuccessfulStatusDataProvider')]
    public function testMarksAFinalUnsuccessfulExtraTransactionAsSkipped(string $status): void
    {
        $orderId = $this->prepareOrderWithTransaction(self::EXISTING_TRANSACTION_ID);
        $this->fakeUnlinkedPayment($orderId, $status);

        $this->objectManager->create(GetOrderIdsByTransactionId::class)->execute(self::UNLINKED_TRANSACTION_ID);

        $this->assertSame(
            [self::EXISTING_TRANSACTION_ID => 0, self::UNLINKED_TRANSACTION_ID => 1],
            $this->getSkippedFlagsByTransactionId($orderId),
        );
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testDoesNotMarkAPaidExtraTransactionAsSkipped(): void
    {
        $orderId = $this->prepareOrderWithTransaction(self::EXISTING_TRANSACTION_ID);
        $this->fakeUnlinkedPayment($orderId);

        $this->objectManager->create(GetOrderIdsByTransactionId::class)->execute(self::UNLINKED_TRANSACTION_ID);

        $this->assertSame(
            [self::EXISTING_TRANSACTION_ID => 0, self::UNLINKED_TRANSACTION_ID => 0],
            $this->getSkippedFlagsByTransactionId($orderId),
        );
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testLeavesAnExpiredExtraTransactionForTheExpiredProcessorToSkip(): void
    {
        $orderId = $this->prepareOrderWithTransaction(self::EXISTING_TRANSACTION_ID);
        $this->fakeUnlinkedPayment($orderId, PaymentStatus::EXPIRED);

        $this->objectManager->create(GetOrderIdsByTransactionId::class)->execute(self::UNLINKED_TRANSACTION_ID);

        $this->assertSame(
            [self::EXISTING_TRANSACTION_ID => 0, self::UNLINKED_TRANSACTION_ID => 0],
            $this->getSkippedFlagsByTransactionId($orderId),
        );
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     * @dataProvider metadataWithoutTheTokenOfTheOrderDataProvider
     */
    #[DataProvider('metadataWithoutTheTokenOfTheOrderDataProvider')]
    public function testDoesNotLinkAnExtraTransactionWithoutThePaymentTokenOfTheOrder(?string $paymentToken): void
    {
        $orderId = $this->prepareOrderWithTransaction(self::EXISTING_TRANSACTION_ID);
        $this->getPaymentTokenOfOrder($orderId);
        $metadata = ['order_id' => $orderId];
        if ($paymentToken !== null) {
            $metadata['payment_token'] = $paymentToken;
        }
        $this->fakeUnlinkedPaymentWithMetadata($metadata);

        $result = $this->objectManager->create(GetOrderIdsByTransactionId::class)
            ->execute(self::UNLINKED_TRANSACTION_ID);

        $this->assertSame([], $result);
        $this->assertSame([], $this->getLinkedTransactionIds($orderId));
    }

    public static function metadataWithoutTheTokenOfTheOrderDataProvider(): array
    {
        return [
            'no payment token' => [null],
            'unknown payment token' => ['token-of-another-shop'],
        ];
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testDoesNotLinkAnExtraTransactionWithAPaymentTokenThatIsNotOfThisOrder(): void
    {
        $orderId = $this->prepareOrderWithTransaction(self::EXISTING_TRANSACTION_ID);
        $this->getPaymentTokenOfOrder($orderId);
        $tokenOfAnotherOrder = $this->objectManager->get(PaymentTokenRepositoryInterface::class)->save(
            $this->objectManager->create(PaymentTokenInterface::class)
                ->setCartId($this->createCartId())
                ->setToken('token-of-another-cart'),
        );
        $this->fakeUnlinkedPaymentWithMetadata([
            'order_id' => $orderId,
            'payment_token' => (string) $tokenOfAnotherOrder->getToken(),
        ]);

        $result = $this->objectManager->create(GetOrderIdsByTransactionId::class)
            ->execute(self::UNLINKED_TRANSACTION_ID);

        $this->assertSame([], $result);
    }

    public static function finalUnsuccessfulStatusDataProvider(): array
    {
        return [
            PaymentStatus::CANCELED => [PaymentStatus::CANCELED],
            PaymentStatus::FAILED => [PaymentStatus::FAILED],
        ];
    }

    /**
     * @return array<string, int>
     */
    private function getSkippedFlagsByTransactionId(int $orderId): array
    {
        $searchCriteria = $this->objectManager->create(SearchCriteriaBuilder::class)
            ->addFilter('order_id', $orderId)
            ->create();
        $links = $this->objectManager->get(TransactionToOrderRepositoryInterface::class)
            ->getList($searchCriteria)
            ->getItems();

        $flags = array_combine(
            array_map(fn (TransactionToOrderInterface $link): string => (string) $link->getTransactionId(), $links),
            array_map(fn (TransactionToOrderInterface $link): int => (int) $link->getSkipped(), $links),
        );
        ksort($flags);

        return $flags;
    }

    private function linkTransaction(string $transactionId, int $orderId): void
    {
        /** @var TransactionToOrderInterface $model */
        $model = $this->objectManager->create(TransactionToOrderInterface::class);
        $model->setTransactionId($transactionId);
        $model->setOrderId($orderId);
        $this->objectManager->get(TransactionToOrderRepositoryInterface::class)->save($model);
    }

    private function prepareOrderWithTransaction(string $transactionId): int
    {
        $order = $this->loadOrderById(self::FIXTURE_ORDER_INCREMENT_ID);
        $order->setMollieTransactionId($transactionId);
        $this->objectManager->get(OrderRepositoryInterface::class)->save($order);

        return (int) $order->getEntityId();
    }

    private function fakeUnlinkedPayment(int $orderId, string $status = PaymentStatus::PAID): void
    {
        $this->fakeUnlinkedPaymentWithMetadata(
            ['order_id' => $orderId, 'payment_token' => $this->getPaymentTokenOfOrder($orderId)],
            $status,
        );
    }

    private function getPaymentTokenOfOrder(int $orderId): string
    {
        $order = $this->objectManager->create(OrderRepositoryInterface::class)->get($orderId);
        $order->setQuoteId($this->createCartId());

        return $this->objectManager->get(PaymentTokenForOrder::class)->execute($order);
    }

    private function createCartId(): int
    {
        $cart = $this->objectManager->create(CartInterface::class);
        $cart->setStoreId(1);
        $this->objectManager->get(CartRepositoryInterface::class)->save($cart);

        return (int) $cart->getId();
    }

    /**
     * @param array<string, int|string> $metadata
     */
    private function fakeUnlinkedPaymentWithMetadata(array $metadata, string $status = PaymentStatus::PAID): void
    {
        $client = \Mollie\Api\MollieApiClient::fake([
            GetPaymentRequest::class => MockResponse::ok((string) json_encode([
                'id' => self::UNLINKED_TRANSACTION_ID,
                'status' => $status,
                'metadata' => $metadata,
            ])),
        ]);

        /** @var FakeMollieApiClient $fakeMollieApiClient */
        $fakeMollieApiClient = $this->objectManager->create(FakeMollieApiClient::class);
        $fakeMollieApiClient->setInstance($client);
        $this->objectManager->addSharedInstance($fakeMollieApiClient, MollieApiClient::class);
    }

    /**
     * @return string[]
     */
    private function getLinkedTransactionIds(int $orderId): array
    {
        $searchCriteria = $this->objectManager->create(SearchCriteriaBuilder::class)
            ->addFilter('order_id', $orderId)
            ->create();

        return array_values(array_map(
            fn (TransactionToOrderInterface $transaction): ?string => $transaction->getTransactionId(),
            $this->objectManager->get(TransactionToOrderRepositoryInterface::class)->getList($searchCriteria)->getItems(),
        ));
    }
}
