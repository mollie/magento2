<?php

declare(strict_types=1);

namespace Mollie\Payment\Test\Integration\Service\Magento;

use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Mollie\Api\Fake\MockResponse;
use Mollie\Api\Http\Requests\GetPaymentRequest;
use Mollie\Payment\Api\Data\TransactionToOrderInterface;
use Mollie\Payment\Api\TransactionToOrderRepositoryInterface;
use Mollie\Payment\Service\Magento\GetOrderIdsByTransactionId;
use Mollie\Payment\Service\Mollie\MollieApiClient;
use Mollie\Payment\Test\Fakes\Service\Mollie\FakeMollieApiClient;
use Mollie\Payment\Test\Integration\IntegrationTestCase;

class GetOrderIdsByTransactionIdTest extends IntegrationTestCase
{
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
}
