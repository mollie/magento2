<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Integration\Controller\Express;

use Magento\Framework\App\Request\Http;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\TestFramework\Quote\Model\GetQuoteByReservedOrderId;
use Magento\TestFramework\TestCase\AbstractController;
use Mollie\Api\Fake\MockResponse;
use Mollie\Api\Http\Requests\GetSessionRequest;
use Mollie\Api\MollieApiClient;
use Mollie\Payment\Config;
use Mollie\Payment\Test\Fakes\Logger\MollieLoggerFake;
use Mollie\Payment\Test\Fakes\Service\Mollie\FakeMollieApiClient;

/**
 * Mollie posts the partial address the customer picked in the Express Component to this endpoint and expects the
 * shipping options the shop offers for it.
 */
class ShippingOptionsTest extends AbstractController
{
    private const SESSION_ID = 'cs_expresssession';

    /**
     * @magentoDataFixture Magento/Checkout/_files/quote_with_address_saved.php
     */
    public function testReturnsTheShippingMethodsForTheAddress(): void
    {
        $cart = $this->loadCartWithSession();

        $this->dispatchCallback($cart, self::SESSION_ID, $this->address());

        $this->assertSame(200, $this->getResponse()->getHttpResponseCode());
        $options = $this->decodeResponse()['options'];

        $this->assertNotEmpty($options);
        $flatrate = $this->findOption($options, 'flatrate_flatrate');
        $this->assertSame('Flat Rate - Fixed', $flatrate['description']);
        $this->assertSame($cart->getQuoteCurrencyCode(), $flatrate['amount']['currency']);
        $this->assertMatchesRegularExpression('/^\d+\.\d{2}$/', $flatrate['amount']['value']);
        $this->assertGreaterThan(0, (float)$flatrate['amount']['value']);
    }

    /**
     * @magentoDataFixture Magento/Checkout/_files/quote_with_address_saved.php
     */
    public function testUsesTheAddressFromTheCallbackToCalculateTheOptions(): void
    {
        $cart = $this->loadCartWithSession();

        $this->dispatchCallback($cart, self::SESSION_ID, $this->address('NL', '1015 CW', 'Amsterdam'));

        $this->assertSame(200, $this->getResponse()->getHttpResponseCode());
        $this->assertNotEmpty($this->decodeResponse()['options']);

        $reloadedCart = $this->objectManager()->get(GetQuoteByReservedOrderId::class)->execute('test_order_1');
        $this->assertSame(
            'US',
            $reloadedCart->getShippingAddress()->getCountryId(),
            'Estimating the options must not overwrite the address on the cart',
        );
    }

    /**
     * @magentoDataFixture Magento/Checkout/_files/quote_with_address_saved.php
     * @magentoConfigFixture current_store carriers/flatrate/active 0
     * @magentoConfigFixture current_store carriers/freeshipping/active 0
     * @magentoConfigFixture current_store carriers/tablerate/active 0
     */
    public function testTellsMollieTheAddressIsNotSupportedWhenThereAreNoShippingMethods(): void
    {
        $cart = $this->loadCartWithSession();

        $this->dispatchCallback($cart, self::SESSION_ID, $this->address());

        $this->assertSame(422, $this->getResponse()->getHttpResponseCode());
        $body = $this->decodeResponse();
        $this->assertSame('https://docs.mollie.com/reference/errors/unsupported-address', $body['type']);
        $this->assertSame(422, $body['status']);
        $this->assertArrayHasKey('detail', $body);
    }

    /**
     * The request is unauthenticated, so the log must not take over whatever was posted.
     *
     * @magentoDataFixture Magento/Checkout/_files/quote_with_address_saved.php
     * @magentoConfigFixture current_store payment/mollie_general/debug 1
     * @magentoConfigFixture current_store carriers/flatrate/active 0
     * @magentoConfigFixture current_store carriers/freeshipping/active 0
     * @magentoConfigFixture current_store carriers/tablerate/active 0
     */
    public function testLogsOnlyKnownAddressFieldsCappedInLength(): void
    {
        $logger = $this->objectManager()->create(MollieLoggerFake::class);
        $this->objectManager()->addSharedInstance(
            $this->objectManager()->create(Config::class, ['logger' => $logger]),
            Config::class,
        );
        $cart = $this->loadCartWithSession();

        $this->dispatchCallback($cart, self::SESSION_ID, [
            'country' => 'US',
            'city' => str_repeat('A', 500),
            'junk' => str_repeat('B', 500),
        ]);

        $this->assertSame(422, $this->getResponse()->getHttpResponseCode());
        $addressLogs = array_values(array_filter(
            $logger->getInfoLogs(),
            static fn (array $log): bool => isset($log['data']['address']),
        ));
        $this->assertCount(1, $addressLogs);
        $loggedAddress = $addressLogs[0]['data']['address'];
        $this->assertArrayNotHasKey('junk', $loggedAddress);
        $this->assertSame('US', $loggedAddress['country']);
        $this->assertSame(64, strlen($loggedAddress['city']));
    }

    /**
     * @magentoDataFixture Magento/Checkout/_files/quote_with_address_saved.php
     */
    public function testRejectsACallbackForAnotherSession(): void
    {
        $cart = $this->loadCartWithSession();

        $this->dispatchCallback($cart, 'cs_someoneelse', $this->address());

        $this->assertSame(403, $this->getResponse()->getHttpResponseCode());
    }

    /**
     * A product page session has no saved quote, so the callback rebuilds it from the session Mollie holds.
     *
     * @magentoDataFixture Magento/Catalog/_files/products.php
     */
    public function testReturnsTheShippingOptionsForAProductPageSession(): void
    {
        $this->fakeSession([
            'placement' => 'product',
            'buyRequest' => ['product' => '1', 'qty' => '1'],
            'paymentToken' => 'producttoken0000000000000000000a',
            'store_id' => 1,
        ]);

        $this->dispatchProductCallback($this->address());

        $this->assertSame(200, $this->getResponse()->getHttpResponseCode());
        $flatrate = $this->findOption($this->decodeResponse()['options'], 'flatrate_flatrate');
        $this->assertSame('5.00', $flatrate['amount']['value']);
    }

    /**
     * @magentoDataFixture Magento/Checkout/_files/quote_with_address_saved.php
     */
    public function testRejectsAProductCallbackForASessionThatIsNotFromAProductPage(): void
    {
        $this->fakeSession(['quoteId' => '1', 'store_id' => 1]);

        $this->dispatchProductCallback($this->address());

        $this->assertSame(403, $this->getResponse()->getHttpResponseCode());
    }

    private function dispatchProductCallback(array $address): void
    {
        $this->getRequest()->setMethod(Http::METHOD_POST);
        $this->getRequest()->setParams(['placement' => 'product']);
        $this->getRequest()->setContent(json_encode([
            'event' => 'shipping-address-changed',
            'sessionId' => self::SESSION_ID,
            'shippingAddress' => $address,
        ]));

        $this->dispatch('mollie/express/shippingOptions');
    }

    private function fakeSession(array $metadata): void
    {
        $client = MollieApiClient::fake([
            GetSessionRequest::class => MockResponse::ok([
                'resource' => 'session',
                'id' => self::SESSION_ID,
                'status' => 'open',
                'metadata' => $metadata,
            ]),
        ]);

        /** @var FakeMollieApiClient $fake */
        $fake = $this->objectManager()->create(FakeMollieApiClient::class);
        $fake->setInstance($client);
        $this->objectManager()->addSharedInstance($fake, \Mollie\Payment\Service\Mollie\MollieApiClient::class);
    }

    private function dispatchCallback(CartInterface $cart, string $sessionId, array $address): void
    {
        $this->getRequest()->setMethod(Http::METHOD_POST);
        $this->getRequest()->setParams(['quoteId' => $cart->getId()]);
        $this->getRequest()->setContent(json_encode([
            'event' => 'shipping-address-changed',
            'sessionId' => $sessionId,
            'shippingAddress' => $address,
        ]));

        $this->dispatch('mollie/express/shippingOptions');
    }

    private function address(string $country = 'US', string $postalCode = '90210', string $city = 'Beverly Hills'): array
    {
        return [
            'postalCode' => $postalCode,
            'city' => $city,
            'region' => '',
            'country' => $country,
        ];
    }

    private function loadCartWithSession(): CartInterface
    {
        $cart = $this->objectManager()->get(GetQuoteByReservedOrderId::class)->execute('test_order_1');
        $cart->getPayment()->setAdditionalInformation('mollie_session_id', self::SESSION_ID);
        $this->objectManager()->get(CartRepositoryInterface::class)->save($cart);

        return $cart;
    }

    private function decodeResponse(): array
    {
        return json_decode($this->getResponse()->getBody(), true, 512, JSON_THROW_ON_ERROR);
    }

    private function findOption(array $options, string $reference): array
    {
        foreach ($options as $option) {
            if ($option['reference'] === $reference) {
                return $option;
            }
        }

        $this->fail('No option with reference ' . $reference . ' found in ' . json_encode($options));
    }

    private function objectManager(): \Magento\TestFramework\ObjectManager
    {
        return $this->_objectManager;
    }
}
