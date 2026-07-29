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

    private function dispatchCreateSession(string $path): void
    {
        $this->getRequest()->setMethod(Http::METHOD_POST);

        $this->dispatch($path);
    }

    private function activateQuoteOnCheckoutSession(): void
    {
        $quote = $this->_objectManager->get(GetQuoteByReservedOrderId::class)->execute('test_order_1');

        $checkoutSession = $this->createStub(Session::class);
        $checkoutSession->method('getQuote')->willReturn($quote);

        $this->_objectManager->addSharedInstance($checkoutSession, Session::class);
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
