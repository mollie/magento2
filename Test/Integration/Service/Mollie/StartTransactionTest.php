<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Integration\Service\Mollie;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Sales\Api\Data\OrderInterface;
use Mollie\Api\Exceptions\ApiException;
use Mollie\Api\Fake\MockResponse;
use Mollie\Api\Http\Requests\GetPaymentRequest;
use Mollie\Payment\Api\Data\MollieCustomerInterface;
use Mollie\Payment\Api\MollieCustomerRepositoryInterface;
use Mollie\Payment\Model\Client\Payments;
use Mollie\Payment\Service\Mollie\StartTransaction;
use Mollie\Payment\Test\Fakes\Service\Mollie\FakeMollieApiClient;
use Mollie\Payment\Test\Integration\IntegrationTestCase;

class StartTransactionTest extends IntegrationTestCase
{
    private const CHECKOUT_URL = 'https://www.mollie.com/checkout/select-method/abc123';

    protected function setUp(): void
    {
        parent::setUp();

        // The OrderLockService interferes with the tests, so we replace it with a fake.
        $this->loadFakeOrderLockService();
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order_with_customer.php
     */
    public function testRemovesTheMollieCustomerAndRetriesWhenItNoLongerExists(): void
    {
        $order = $this->loadOrder('100000001');
        $this->storeMollieCustomerId($order, 'cst_no_longer_available');

        $paymentsApiMock = $this->createMock(Payments::class);
        $paymentsApiMock->expects($this->exactly(2))->method('startTransaction')->willReturnOnConsecutiveCalls(
            $this->throwException($this->createApiException(410, 'Gone', 'The customer is no longer available')),
            static::CHECKOUT_URL,
        );

        $result = $this->createInstance($paymentsApiMock)->execute($order);

        $this->assertEquals(static::CHECKOUT_URL, $result);
        $this->assertNull($this->getMollieCustomer($order));
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order_with_customer.php
     */
    public function testKeepsTheMollieCustomerWhenTheErrorIsUnrelated(): void
    {
        $order = $this->loadOrder('100000001');
        $this->storeMollieCustomerId($order, 'cst_still_valid');

        $exception = $this->createApiException(422, 'Unprocessable Entity', 'The amount is invalid');

        $paymentsApiMock = $this->createMock(Payments::class);
        $paymentsApiMock->expects($this->once())->method('startTransaction')->willThrowException($exception);

        $this->expectExceptionObject($exception);

        try {
            $this->createInstance($paymentsApiMock)->execute($order);
        } finally {
            $this->assertNotNull($this->getMollieCustomer($order));
        }
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order_with_customer.php
     */
    public function testDoesNotRetryWhenThereIsNoMollieCustomerStored(): void
    {
        $order = $this->loadOrder('100000001');

        $exception = $this->createApiException(410, 'Gone', 'The customer is no longer available');

        $paymentsApiMock = $this->createMock(Payments::class);
        $paymentsApiMock->expects($this->once())->method('startTransaction')->willThrowException($exception);

        $this->expectExceptionObject($exception);

        $this->createInstance($paymentsApiMock)->execute($order);
    }

    private function createInstance(Payments $paymentsApi): StartTransaction
    {
        $apiClient = $this->objectManager->create(FakeMollieApiClient::class);
        $apiClient->setInstance(new \Mollie\Api\MollieApiClient());

        return $this->objectManager->create(StartTransaction::class, [
            'paymentsApi' => $paymentsApi,
            'mollieApiClient' => $apiClient,
        ]);
    }

    private function createApiException(int $status, string $title, string $detail): ApiException
    {
        $client = \Mollie\Api\MollieApiClient::fake([
            GetPaymentRequest::class => MockResponse::error($status, $title, $detail),
        ]);

        try {
            $client->send(new GetPaymentRequest('tr_dummy'));
        } catch (ApiException $exception) {
            return $exception;
        }

        $this->fail('Expected the faked request to result in an ApiException');
    }

    private function storeMollieCustomerId(OrderInterface $order, string $mollieCustomerId): void
    {
        /** @var ResourceConnection $resource */
        $resource = $this->objectManager->get(ResourceConnection::class);

        $resource->getConnection()->insert($resource->getTableName('mollie_payment_customer'), [
            MollieCustomerInterface::CUSTOMER_ID => (int)$order->getCustomerId(),
            MollieCustomerInterface::MOLLIE_CUSTOMER_ID => $mollieCustomerId,
        ]);
    }

    private function getMollieCustomer(OrderInterface $order): ?MollieCustomerInterface
    {
        $customer = $this->objectManager->get(CustomerRepositoryInterface::class)
            ->getById((int)$order->getCustomerId());

        return $this->objectManager->get(MollieCustomerRepositoryInterface::class)->getByCustomer($customer);
    }
}
