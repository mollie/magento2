<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Integration\Controller\Express;

use Magento\Checkout\Model\Session;
use Magento\Framework\App\Request\Http;
use Magento\TestFramework\Quote\Model\GetQuoteByReservedOrderId;
use Magento\TestFramework\TestCase\AbstractController;
use Mollie\Api\Fake\MockResponse;
use Mollie\Api\Http\PendingRequest;
use Mollie\Api\MollieApiClient;
use Mollie\Payment\Service\Mollie\Api\CreateSessionRequest;
use Mollie\Payment\Test\Fakes\Service\Mollie\FakeMollieApiClient;

class CreateSessionTest extends AbstractController
{
    /**
     * @magentoDataFixture Magento/Checkout/_files/quote_with_address_saved.php
     */
    public function testDoesNotCreateACheckoutSessionWhenNoShippingMethodIsSelected(): void
    {
        $client = $this->fakeSessionRequest();
        $this->activateQuoteOnCheckoutSession();

        $this->dispatchCreateSession('mollie/express/createSession/type/checkout');

        $this->assertEquals(409, $this->getResponse()->getHttpResponseCode());
        $client->assertSentCount(0);
    }

    /**
     * @magentoDataFixture Magento/Checkout/_files/quote_with_address_and_shipping_method_saved.php
     */
    public function testCreatesACheckoutSessionWhenAShippingMethodIsSelected(): void
    {
        $client = $this->fakeSessionRequest();
        $this->activateQuoteOnCheckoutSession();

        $this->dispatchCreateSession('mollie/express/createSession/type/checkout');

        $this->assertEquals(200, $this->getResponse()->getHttpResponseCode());
        $client->assertSentCount(1);
    }

    /**
     * The cart page has no shipping method yet, so it must not be blocked by the checkout requirement.
     *
     * @magentoDataFixture Magento/Checkout/_files/quote_with_address_saved.php
     */
    public function testCreatesACartSessionWithoutAShippingMethod(): void
    {
        $client = $this->fakeSessionRequest();
        $this->activateQuoteOnCheckoutSession();

        $this->dispatchCreateSession('mollie/express/createSession/type/cart');

        $this->assertEquals(200, $this->getResponse()->getHttpResponseCode());
        $client->assertSentCount(1);
    }

    /**
     * The cart page has no shipping method yet, so Mollie has to offer the shipping options.
     *
     * @magentoDataFixture Magento/Checkout/_files/quote_with_address_saved.php
     * @magentoConfigFixture current_store payment/mollie_methods_expresscomponents/beta_shipping_options 1
     */
    public function testLetsMollieOfferTheShippingOptionsOnTheCartPage(): void
    {
        $this->assertSessionLetsMollieOfferTheShippingOptions('mollie/express/createSession/type/cart');
    }

    /**
     * @magentoDataFixture Magento/Checkout/_files/quote_with_address_saved.php
     * @magentoConfigFixture current_store payment/mollie_methods_expresscomponents/beta_shipping_options 1
     */
    public function testLetsMollieOfferTheShippingOptionsOnTheShippingStep(): void
    {
        $this->assertSessionLetsMollieOfferTheShippingOptions('mollie/express/createSession/type/shipping');
    }

    /**
     * @magentoDataFixture Magento/Checkout/_files/quote_with_address_saved.php
     * @magentoConfigFixture current_store payment/mollie_methods_expresscomponents/beta_shipping_options 1
     */
    public function testLetsMollieOfferTheShippingOptionsInTheMinicart(): void
    {
        $this->assertSessionLetsMollieOfferTheShippingOptions('mollie/express/createSession');
    }

    /**
     * The product page must not save a quote for every visitor, so the session carries what is needed to build the
     * quote again in the webhook and the shipping options callback.
     *
     * @magentoDataFixture Magento/Catalog/_files/products.php
     * @magentoConfigFixture current_store payment/mollie_methods_expresscomponents/beta_shipping_options 1
     */
    public function testCreatesAProductPageSessionWithoutSavingAQuote(): void
    {
        $client = $this->fakeSessionRequest();
        $quotesBefore = $this->countQuotes();

        $this->getRequest()->setParams(['product' => '1', 'qty' => '1']);
        $this->dispatchCreateSession('mollie/express/createSession/type/product');

        $this->assertEquals(200, $this->getResponse()->getHttpResponseCode());
        $this->assertSame($quotesBefore, $this->countQuotes());
        $client->assertSent(function (PendingRequest $request): bool {
            $body = json_decode((string)$request->createPsrRequest()->getBody(), true);

            $this->assertSame('10.00', $body['amount']['value']);
            $this->assertSame('product', $body['metadata']['placement']);
            $this->assertSame(['product' => '1', 'qty' => '1'], $body['metadata']['buyRequest']);
            $this->assertMatchesRegularExpression('/^[A-Za-z0-9]{32}$/', $body['metadata']['paymentToken']);
            $this->assertArrayNotHasKey('quoteId', $body['metadata']);
            $this->assertStringContainsString('placement=product&storeId=1', $body['shipping']['callbackUrl']);
            $this->assertStringContainsString('placement=product&storeId=1', $body['payment']['webhookUrl']);
            $this->assertStringContainsString($body['metadata']['paymentToken'], $body['redirectUrl']);
            $this->assertStringContainsString('storeId=1', $body['redirectUrl']);
            $this->assertEqualsCanonicalizing(
                ['email', 'billing-address', 'shipping-address'],
                $body['requiredCustomerDetails'],
            );

            return true;
        });
    }

    /**
     * A configurable product without its options, or an unknown product, cannot be bought yet.
     * @magentoConfigFixture current_store payment/mollie_methods_expresscomponents/beta_shipping_options 1
     */
    public function testRefusesAProductPageSessionForAProductThatCannotBeAdded(): void
    {
        $client = $this->fakeSessionRequest();

        $this->getRequest()->setParams(['product' => '999999', 'qty' => '1']);
        $this->dispatchCreateSession('mollie/express/createSession/type/product');

        $this->assertEquals(422, $this->getResponse()->getHttpResponseCode());
        $client->assertSentCount(0);
    }

    /**
     * Without the beta the cart page keeps charging the grand total, as it did before the shipping options.
     *
     * @magentoDataFixture Magento/Checkout/_files/quote_with_address_and_shipping_method_saved.php
     * @magentoConfigFixture current_store payment/mollie_methods_expresscomponents/beta_shipping_options 0
     */
    public function testCartSessionDoesNotLetMollieOfferTheShippingOptionsWhenTheBetaIsDisabled(): void
    {
        $client = $this->fakeSessionRequest();
        $this->activateQuoteOnCheckoutSession();

        $this->dispatchCreateSession('mollie/express/createSession/type/cart');

        $client->assertSent(function (PendingRequest $request): bool {
            $body = json_decode((string)$request->createPsrRequest()->getBody(), true);

            $this->assertArrayNotHasKey('shipping', $body);
            $this->assertContains('shipping_fee', array_column($body['lines'], 'type'));

            return true;
        });
    }

    /**
     * @magentoDataFixture Magento/Catalog/_files/products.php
     * @magentoConfigFixture current_store payment/mollie_methods_expresscomponents/beta_shipping_options 0
     */
    public function testRefusesAProductPageSessionWhenTheBetaIsDisabled(): void
    {
        $client = $this->fakeSessionRequest();

        $this->getRequest()->setParams(['product' => '1', 'qty' => '1']);
        $this->dispatchCreateSession('mollie/express/createSession/type/product');

        $this->assertEquals(404, $this->getResponse()->getHttpResponseCode());
        $client->assertSentCount(0);
    }

    /**
     * The minicart has no guest check of its own. Without the beta a guest cart would make Mollie answer with an
     * error on every minicart open, so the endpoint answers without calling Mollie.
     *
     * @magentoDataFixture Magento/Checkout/_files/quote_with_address_saved.php
     * @magentoConfigFixture current_store payment/mollie_methods_expresscomponents/beta_shipping_options 0
     */
    public function testAnswersWithoutCallingMollieWhenAGuestCartNeedsCustomerDetailsWithoutTheBeta(): void
    {
        $client = $this->fakeSessionRequest();
        $quote = $this->activateQuoteOnCheckoutSession();
        $quote->setCustomerEmail(null);

        $this->dispatchCreateSession('mollie/express/createSession');

        $this->assertEquals(422, $this->getResponse()->getHttpResponseCode());
        $client->assertSentCount(0);
    }

    private function countQuotes(): int
    {
        return $this->_objectManager->create(\Magento\Quote\Model\ResourceModel\Quote\CollectionFactory::class)
            ->create()
            ->getSize();
    }

    private function assertSessionLetsMollieOfferTheShippingOptions(string $path): void
    {
        $client = $this->fakeSessionRequest();
        $this->activateQuoteOnCheckoutSession();

        $this->dispatchCreateSession($path);

        $client->assertSent(function (PendingRequest $request): bool {
            $body = json_decode((string)$request->createPsrRequest()->getBody(), true);

            $this->assertArrayHasKey('shipping', $body);
            $this->assertArrayHasKey('callbackUrl', $body['shipping']);
            $this->assertNotContains('shipping_fee', array_column($body['lines'], 'type'));

            return true;
        });
    }

    private function dispatchCreateSession(string $path): void
    {
        $this->getRequest()->setMethod(Http::METHOD_POST);

        $this->dispatch($path);
    }

    private function activateQuoteOnCheckoutSession(): \Magento\Quote\Api\Data\CartInterface
    {
        $quote = $this->_objectManager->get(GetQuoteByReservedOrderId::class)->execute('test_order_1');

        $checkoutSession = $this->createStub(Session::class);
        $checkoutSession->method('getQuote')->willReturn($quote);

        $this->_objectManager->addSharedInstance($checkoutSession, Session::class);

        return $quote;
    }

    private function fakeSessionRequest(): MollieApiClient
    {
        $client = MollieApiClient::fake([
            CreateSessionRequest::class => MockResponse::ok('session'),
        ], true);

        /** @var FakeMollieApiClient $fake */
        $fake = $this->_objectManager->create(FakeMollieApiClient::class);
        $fake->setInstance($client);
        $this->_objectManager->addSharedInstance($fake, \Mollie\Payment\Service\Mollie\MollieApiClient::class);

        return $client;
    }
}
