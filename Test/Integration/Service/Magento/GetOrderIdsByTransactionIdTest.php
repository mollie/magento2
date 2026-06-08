<?php

declare(strict_types=1);

namespace Mollie\Payment\Test\Integration\Service\Magento;

use Magento\Sales\Api\OrderRepositoryInterface;
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
