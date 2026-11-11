<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Integration\Controller\Express;

use Magento\Framework\App\Request\Http;
use Magento\TestFramework\TestCase\AbstractController;
use Mollie\Api\Fake\MockResponse;
use Mollie\Api\Http\Requests\GetPaymentRequest;
use Mollie\Api\Http\Requests\UpdatePaymentRequest;
use Mollie\Api\MollieApiClient;
use Mollie\Payment\Api\PaymentTokenRepositoryInterface;
use Mollie\Payment\Model\Client\Payments\ProcessTransaction;
use Mollie\Payment\Service\LockService;
use Mollie\Payment\Service\Mollie\Order\GetOrderByTransactionId;
use Mollie\Payment\Service\OrderLockService;
use Mollie\Payment\Test\Fakes\Model\Client\Payments\ProcessTransactionFake;
use Mollie\Payment\Test\Fakes\Service\LockServiceFake;
use Mollie\Payment\Test\Fakes\Service\Mollie\FakeMollieApiClient;
use Mollie\Payment\Test\Fakes\Service\OrderLockServiceFake;

/**
 * @magentoAppArea frontend
 */
class WebhookTest extends AbstractController
{
    private const TRANSACTION_ID = 'tr_productpagewebhook';
    private const TOKEN = 'producttoken0000000000000000000b';

    /**
     * A product page session has no saved quote. The webhook builds it from the payment metadata, places the order
     * and gives the token from the redirect URL its row, so the wait page finds the order.
     *
     * @magentoDataFixture Magento/Catalog/_files/products.php
     */
    public function testPlacesTheOrderForAProductPagePayment(): void
    {
        $this->fakeMollie($this->productPagePayment());

        $this->dispatchWebhook(['id' => self::TRANSACTION_ID, 'placement' => 'product']);

        $this->assertSame(201, $this->getResponse()->getHttpResponseCode());
        $order = $this->_objectManager->get(GetOrderByTransactionId::class)->execute(self::TRANSACTION_ID);
        $this->assertNotNull($order);
        $this->assertSame('simple', $order->getItems()[array_key_first($order->getItems())]->getSku());
        $this->assertSame('flatrate_flatrate', $order->getShippingMethod());
        $this->assertEquals(
            $order->getEntityId(),
            $this->_objectManager->get(PaymentTokenRepositoryInterface::class)->getByToken(self::TOKEN)?->getOrderId(),
        );
    }

    /**
     * Without a quote id the webhook only accepts payments that were started on a product page.
     *
     * @magentoDataFixture Magento/Catalog/_files/products.php
     */
    public function testRefusesAPaymentWithoutQuoteThatIsNotFromAProductPage(): void
    {
        $payment = $this->productPagePayment();
        $payment['metadata'] = ['quoteId' => '1', 'store_id' => 1];
        $this->fakeMollie($payment);

        $this->expectException(\Magento\Framework\Exception\LocalizedException::class);

        $this->dispatchWebhook(['id' => self::TRANSACTION_ID, 'placement' => 'product']);
    }

    private function dispatchWebhook(array $params): void
    {
        $this->getRequest()->setMethod(Http::METHOD_POST);
        $this->getRequest()->setParams($params);

        $this->dispatch('mollie/express/webhook');
    }

    private function fakeMollie(array $payment): void
    {
        $client = MollieApiClient::fake([
            GetPaymentRequest::class => MockResponse::ok($payment),
            UpdatePaymentRequest::class => MockResponse::ok($payment),
        ]);

        /** @var FakeMollieApiClient $fake */
        $fake = $this->_objectManager->create(FakeMollieApiClient::class);
        $fake->setInstance($client);
        $this->_objectManager->addSharedInstance($fake, \Mollie\Payment\Service\Mollie\MollieApiClient::class);

        /** @var ProcessTransactionFake $processTransaction */
        $processTransaction = $this->_objectManager->create(ProcessTransactionFake::class);
        $processTransaction->disableParentCall();
        $this->_objectManager->addSharedInstance($processTransaction, ProcessTransaction::class);
        $this->_objectManager->addSharedInstance(
            $this->_objectManager->create(LockServiceFake::class),
            LockService::class,
        );
        $this->_objectManager->addSharedInstance(
            $this->_objectManager->create(OrderLockServiceFake::class),
            OrderLockService::class,
        );
    }

    private function productPagePayment(): array
    {
        return [
            'resource' => 'payment',
            'id' => self::TRANSACTION_ID,
            'status' => 'paid',
            'method' => 'ideal',
            'amount' => ['value' => '15.00', 'currency' => 'USD'],
            'metadata' => [
                'placement' => 'product',
                'buyRequest' => ['product' => '1', 'qty' => '1'],
                'paymentToken' => self::TOKEN,
                'store_id' => 1,
            ],
            'lines' => [
                $this->line('physical', '[simple] Simple Product', '10.00'),
                $this->line('shipping_fee', 'Flat Rate - Fixed', '5.00') + ['reference' => 'flatrate_flatrate'],
            ],
            'billingAddress' => $this->address() + ['email' => 'productpage@example.com'],
            'shippingAddress' => $this->address(),
            '_links' => ['self' => ['href' => 'https://api.mollie.com/v2/payments/' . self::TRANSACTION_ID]],
        ];
    }

    private function line(string $type, string $description, string $value): array
    {
        return [
            'type' => $type,
            'description' => $description,
            'quantity' => 1,
            'unitPrice' => ['value' => $value, 'currency' => 'USD'],
            'totalAmount' => ['value' => $value, 'currency' => 'USD'],
        ];
    }

    private function address(): array
    {
        return [
            'givenName' => 'John',
            'familyName' => 'Doe',
            'streetAndNumber' => 'Keizersgracht 126',
            'postalCode' => '1015 CW',
            'city' => 'Amsterdam',
            'country' => 'NL',
        ];
    }
}
