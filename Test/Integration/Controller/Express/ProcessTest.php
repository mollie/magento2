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
use Mollie\Api\Http\Requests\GetSessionRequest;
use Mollie\Api\MollieApiClient;
use Mollie\Payment\Service\Mollie\Express\ProductBuyRequest;
use Mollie\Payment\Service\Mollie\Express\ProductPaymentToken;
use Mollie\Payment\Service\Mollie\Express\ProductQuote;
use Mollie\Payment\Service\Mollie\Express\ProductSessions;
use Mollie\Payment\Service\Mollie\Order\ConvertComponentsPaymentToOrder;
use Mollie\Payment\Test\Integration\ExpressPaymentBuilder;
use Mollie\Payment\Test\Fakes\Service\Mollie\FakeMollieApiClient;

/**
 * The redirect of a product page session arrives before the webhook may have created the order and its payment
 * token, so it cannot rely on a token or a cart.
 *
 * @magentoAppArea frontend
 */
class ProcessTest extends AbstractController
{
    private const TOKEN = 'producttoken0000000000000000000a';
    private const SESSION_ID = 'cs_productpage';

    public function testShowsTheWaitPageForACompletedProductPageSession(): void
    {
        $this->givenProductPageSession('completed');

        $this->dispatchProcess();

        $this->assertSame(200, $this->getResponse()->getHttpResponseCode());
        $this->assertStringContainsString('mollie-order-status', $this->getResponse()->getBody());
        $this->assertStringContainsString(self::TOKEN, $this->getResponse()->getBody());
    }

    public function testSendsTheCustomerBackToTheCartWhenTheProductPageSessionIsNotCompleted(): void
    {
        $this->givenProductPageSession('open');

        $this->dispatchProcess();

        $this->assertRedirect($this->stringContains('checkout/cart'));
    }

    /**
     * Banking apps often open the redirect in another browser, which does not know the product page session. Until
     * the webhook created the order the token does not exist, so the wait page waits for it.
     */
    public function testShowsTheWaitPageForAProductPageTokenThatHasNoOrderYet(): void
    {
        $this->dispatchProcess();

        $this->assertSame(200, $this->getResponse()->getHttpResponseCode());
        $this->assertStringContainsString(self::TOKEN, $this->getResponse()->getBody());
    }

    /**
     * Once the webhook placed a product page order, the token belongs to the cart of that order, which has no
     * session id. The order exists, so the payment was completed.
     *
     * @magentoDataFixture Magento/Checkout/_files/quote_with_address_and_shipping_method_saved.php
     */
    public function testShowsTheWaitPageForAProductPageTokenThatAlreadyHasAnOrder(): void
    {
        $order = $this->placeProductPageOrder();
        $this->_objectManager->create(ProductPaymentToken::class)->link($order, self::TOKEN);

        $this->dispatchProcess();

        $this->assertSame(200, $this->getResponse()->getHttpResponseCode());
        $this->assertStringContainsString(self::TOKEN, $this->getResponse()->getBody());
    }

    private function placeProductPageOrder(): \Magento\Sales\Api\Data\OrderInterface
    {
        $quote = $this->_objectManager->create(ProductQuote::class)
            ->create(ProductBuyRequest::fromRequestParams(['product' => '1', 'qty' => '1']), 1, null);
        $payment = $this->_objectManager->get(ExpressPaymentBuilder::class)->build('tr_productprocess');
        $payment->billingAddress->email = 'productprocess@example.com';
        $payment->shippingAddress->email = 'productprocess@example.com';

        return $this->_objectManager->create(ConvertComponentsPaymentToOrder::class)->execute($quote, $payment);
    }

    private function givenProductPageSession(string $status): void
    {
        $client = MollieApiClient::fake([
            GetSessionRequest::class => MockResponse::ok([
                'resource' => 'session',
                'id' => self::SESSION_ID,
                'status' => $status,
            ]),
        ]);

        /** @var FakeMollieApiClient $fake */
        $fake = $this->_objectManager->create(FakeMollieApiClient::class);
        $fake->setInstance($client);
        $this->_objectManager->addSharedInstance($fake, \Mollie\Payment\Service\Mollie\MollieApiClient::class);

        $this->_objectManager->get(ProductSessions::class)->remember(self::TOKEN, self::SESSION_ID);
    }

    private function dispatchProcess(): void
    {
        $this->getRequest()->setMethod(Http::METHOD_GET);
        $this->getRequest()->setParams(['paymentToken' => self::TOKEN]);

        $this->dispatch('mollie/express/process');
    }
}
