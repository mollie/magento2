<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Integration\Service\Mollie;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Quote\Api\CartTotalRepositoryInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\TestFramework\Quote\Model\GetQuoteByReservedOrderId;
use Mollie\Api\Fake\MockResponse;
use Mollie\Api\Http\Data\Money;
use Mollie\Api\Http\PendingRequest;
use Mollie\Api\MollieApiClient;
use Mollie\Payment\Exceptions\CustomerDetailsRequired;
use Mollie\Payment\Service\Mollie\Api\CreateSessionRequest;
use Mollie\Payment\Service\Mollie\CreateSession;
use Mollie\Payment\Test\Fakes\Service\Mollie\FakeMollieApiClient;
use Mollie\Payment\Test\Integration\IntegrationTestCase;

/**
 * @magentoAppArea frontend
 */
class CreateSessionTest extends IntegrationTestCase
{
    /**
     * @magentoDataFixture Magento/Checkout/_files/quote_with_address_and_shipping_method_saved.php
     */
    public function testTagsEveryLineWithATypeAndAddsShippingOnTheCheckoutPath(): void
    {
        $client = $this->fakeSessionRequest();

        /** @var CreateSession $instance */
        $instance = $this->objectManager->create(CreateSession::class);
        $instance->execute($this->loadQuote(), false);

        $client->assertSent(function (PendingRequest $request): bool {
            $lines = $this->extractLines($request);

            foreach ($lines as $line) {
                $this->assertArrayHasKey('type', $line);
            }

            $types = array_column($lines, 'type');
            $this->assertContains('physical', $types);
            $this->assertContains('shipping_fee', $types);

            return true;
        });
    }

    /**
     * @magentoDataFixture Magento/Checkout/_files/quote_with_address_and_shipping_method_saved.php
     */
    public function testExpressPathSendsOnlyProductLinesTaggedPhysical(): void
    {
        $client = $this->fakeSessionRequest();

        /** @var CreateSession $instance */
        $instance = $this->objectManager->create(CreateSession::class);
        $instance->execute($this->loadQuote(), true);

        $client->assertSent(function (PendingRequest $request): bool {
            $lines = $this->extractLines($request);

            $this->assertNotEmpty($lines);
            foreach ($lines as $line) {
                $this->assertSame('physical', $line['type'] ?? null);
            }

            $this->assertNotContains('shipping_fee', array_column($lines, 'type'));

            return true;
        });
    }

    /**
     * @magentoDataFixture Magento/Checkout/_files/quote_with_address_and_shipping_method_saved.php
     */
    public function testChargesTheGrandTotalIncludingShippingOnTheCheckoutPath(): void
    {
        $client = $this->fakeSessionRequest();

        $cart = $this->loadQuote();
        $shippingCosts = (float)$cart->getShippingAddress()->getShippingInclTax();
        $grandTotal = (float)$this->objectManager->get(CartTotalRepositoryInterface::class)
            ->get($cart->getId())
            ->getGrandTotal();

        $this->assertGreaterThan(0, $shippingCosts, 'The quote must have shipping costs for this test to be useful');

        /** @var CreateSession $instance */
        $instance = $this->objectManager->create(CreateSession::class);
        $instance->execute($cart, false);

        $client->assertSent(function (PendingRequest $request) use ($grandTotal): bool {
            $this->assertSame(number_format($grandTotal, 2, '.', ''), $this->extractAmount($request)['value']);

            return true;
        });
    }

    /**
     * @magentoDataFixture Magento/Checkout/_files/quote_with_address_and_shipping_method_saved.php
     */
    public function testExpressPathChargesTheSubtotal(): void
    {
        $client = $this->fakeSessionRequest();

        $cart = $this->loadQuote();
        $subtotal = (float)$this->objectManager->get(CartTotalRepositoryInterface::class)
            ->get($cart->getId())
            ->getSubtotalInclTax();

        /** @var CreateSession $instance */
        $instance = $this->objectManager->create(CreateSession::class);
        $instance->execute($cart, true);

        $client->assertSent(function (PendingRequest $request) use ($subtotal): bool {
            $this->assertSame(number_format($subtotal, 2, '.', ''), $this->extractAmount($request)['value']);

            return true;
        });
    }

    /**
     * @magentoDataFixture Magento/Checkout/_files/quote_with_address_and_shipping_method_saved.php
     * @magentoConfigFixture current_store payment/mollie_methods_expresscomponents/beta_shipping_options 1
     */
    public function testAsksMollieToCollectTheEmailWhenTheCartHasNone(): void
    {
        $client = $this->fakeSessionRequest();
        $cart = $this->loadQuote();
        $cart->setCustomerEmail(null);

        /** @var CreateSession $instance */
        $instance = $this->objectManager->create(CreateSession::class);
        $instance->execute($cart, true);

        $client->assertSent(function (PendingRequest $request): bool {
            $this->assertContains('email', $this->extractField($request, 'requiredCustomerDetails'));

            return true;
        });
    }

    /**
     * @magentoDataFixture Magento/Checkout/_files/quote_with_address_and_shipping_method_saved.php
     */
    public function testDoesNotAskForTheEmailWhenTheCartHasOne(): void
    {
        $client = $this->fakeSessionRequest();
        $cart = $this->loadQuote();
        $cart->setCustomerEmail('customer@example.com');

        /** @var CreateSession $instance */
        $instance = $this->objectManager->create(CreateSession::class);
        $instance->execute($cart, true);

        $client->assertSent(function (PendingRequest $request): bool {
            $this->assertNotContains('email', $this->extractField($request, 'requiredCustomerDetails') ?? []);

            return true;
        });
    }

    /**
     * @magentoDataFixture Magento/Checkout/_files/quote_with_address_and_shipping_method_saved.php
     */
    public function testDoesNotAskForTheEmailWhenTheGuestAlreadyEnteredIt(): void
    {
        $client = $this->fakeSessionRequest();
        $cart = $this->loadQuote();
        $cart->setCustomerEmail(null);
        $cart->getPayment()->setAdditionalInformation('mollie_guest_email', 'guest@example.com');

        /** @var CreateSession $instance */
        $instance = $this->objectManager->create(CreateSession::class);
        $instance->execute($cart, false);

        $client->assertSent(function (PendingRequest $request): bool {
            $this->assertNull($this->extractField($request, 'requiredCustomerDetails'));

            return true;
        });
    }

    /**
     * @magentoDataFixture Magento/Checkout/_files/quote_with_address_and_shipping_method_saved.php
     * @magentoConfigFixture current_store payment/mollie_methods_expresscomponents/beta_shipping_options 1
     */
    public function testAsksMollieToCollectTheAddressesWhenTheCartHasNone(): void
    {
        $client = $this->fakeSessionRequest();
        $cart = $this->loadQuote();
        $cart->setCustomerEmail('customer@example.com');
        $cart->getBillingAddress()->setStreet([]);
        $cart->getShippingAddress()->setStreet([]);
        $cart->getCustomer()->setDefaultBilling(null);
        $cart->getCustomer()->setDefaultShipping(null);

        /** @var CreateSession $instance */
        $instance = $this->objectManager->create(CreateSession::class);
        $instance->execute($cart, true);

        $client->assertSent(function (PendingRequest $request): bool {
            $this->assertSame(
                ['billing-address', 'shipping-address'],
                $this->extractField($request, 'requiredCustomerDetails'),
            );

            return true;
        });
    }

    /**
     * Mollie asks the shop for the shipping options once the customer picked an address in the Express Component.
     *
     * @magentoDataFixture Magento/Checkout/_files/quote_with_address_and_shipping_method_saved.php
     * @magentoConfigFixture current_store payment/mollie_methods_expresscomponents/beta_shipping_options 1
     */
    public function testLetsMollieFetchTheShippingOptionsOnTheExpressPath(): void
    {
        $client = $this->fakeSessionRequest();
        $cart = $this->loadQuote();

        /** @var CreateSession $instance */
        $instance = $this->objectManager->create(CreateSession::class);
        $instance->execute($cart, true);

        $client->assertSent(function (PendingRequest $request) use ($cart): bool {
            $shipping = $this->extractField($request, 'shipping');

            $this->assertIsArray($shipping);
            $this->assertStringContainsString('mollie/express/shippingOptions', $shipping['callbackUrl']);
            $this->assertStringContainsString('quoteId=' . $cart->getId(), $shipping['callbackUrl']);
            $this->assertContains('shipping-address', $this->extractField($request, 'requiredCustomerDetails'));

            return true;
        });
    }

    /**
     * Mollie rejects the shipping options for accounts that are not in its beta, which would break the express
     * session on the cart page and in the minicart.
     *
     * @magentoDataFixture Magento/Checkout/_files/quote_with_address_and_shipping_method_saved.php
     * @magentoConfigFixture current_store payment/mollie_methods_expresscomponents/beta_shipping_options 0
     */
    public function testDoesNotLetMollieFetchTheShippingOptionsWhenTheBetaIsDisabled(): void
    {
        $client = $this->fakeSessionRequest();
        $cart = $this->loadQuote();
        $cart->setCustomerEmail('customer@example.com');

        /** @var CreateSession $instance */
        $instance = $this->objectManager->create(CreateSession::class);
        $instance->execute($cart, true);

        $client->assertSent(function (PendingRequest $request): bool {
            $this->assertNull($this->extractField($request, 'shipping'));
            $this->assertNull(
                $this->extractField($request, 'requiredCustomerDetails'),
                'A cart with an email and addresses must not ask Mollie for customer details without the beta',
            );

            return true;
        });
    }

    /**
     * On the checkout path the shipping method is already selected and sent as a line.
     *
     * @magentoDataFixture Magento/Checkout/_files/quote_with_address_and_shipping_method_saved.php
     * @magentoConfigFixture current_store payment/mollie_methods_expresscomponents/beta_shipping_options 1
     */
    public function testDoesNotLetMollieFetchTheShippingOptionsOnTheCheckoutPath(): void
    {
        $client = $this->fakeSessionRequest();

        /** @var CreateSession $instance */
        $instance = $this->objectManager->create(CreateSession::class);
        $instance->execute($this->loadQuote(), false);

        $client->assertSent(function (PendingRequest $request): bool {
            $this->assertNull($this->extractField($request, 'shipping'));

            return true;
        });
    }

    /**
     * @magentoDataFixture Magento/Checkout/_files/quote_with_virtual_product_and_address.php
     * @magentoConfigFixture current_store payment/mollie_methods_expresscomponents/beta_shipping_options 1
     */
    public function testDoesNotLetMollieFetchTheShippingOptionsForAVirtualCart(): void
    {
        $client = $this->fakeSessionRequest();
        $cart = $this->objectManager->get(GetQuoteByReservedOrderId::class)->execute('test_order_with_virtual_product');

        /** @var CreateSession $instance */
        $instance = $this->objectManager->create(CreateSession::class);
        $instance->execute($cart, true);

        $client->assertSent(function (PendingRequest $request): bool {
            $this->assertNull($this->extractField($request, 'shipping'));
            $this->assertNotContains(
                'shipping-address',
                $this->extractField($request, 'requiredCustomerDetails') ?? [],
            );

            return true;
        });
    }

    /**
     * Without a reachable webhook URL Mollie cannot call back for the options either, so fall back to the previous
     * behaviour instead of blocking the payment with a timeout.
     *
     * @magentoDataFixture Magento/Checkout/_files/quote_with_address_and_shipping_method_saved.php
     * @magentoConfigFixture current_store payment/mollie_general/type test
     * @magentoConfigFixture current_store payment/mollie_general/use_webhooks disabled
     * @magentoConfigFixture current_store payment/mollie_methods_expresscomponents/beta_shipping_options 1
     */
    public function testDoesNotLetMollieFetchTheShippingOptionsWhenMollieCannotReachTheShop(): void
    {
        $client = $this->fakeSessionRequest();

        /** @var CreateSession $instance */
        $instance = $this->objectManager->create(CreateSession::class);
        $instance->execute($this->loadQuote(), true);

        $client->assertSent(function (PendingRequest $request): bool {
            $this->assertNull($this->extractField($request, 'shipping'));

            return true;
        });
    }

    /**
     * Mollie does not know requiredCustomerDetails for accounts outside its beta and answers with an error, which
     * would be logged on every page view of a guest. So the session is not created at all.
     *
     * @magentoDataFixture Magento/Checkout/_files/quote_with_address_and_shipping_method_saved.php
     * @magentoConfigFixture current_store payment/mollie_methods_expresscomponents/beta_shipping_options 0
     */
    public function testDoesNotCallMollieWhenCustomerDetailsAreMissingWithoutTheBeta(): void
    {
        $client = $this->fakeSessionRequest();
        $cart = $this->loadQuote();
        $cart->setCustomerEmail(null);

        /** @var CreateSession $instance */
        $instance = $this->objectManager->create(CreateSession::class);

        try {
            $instance->execute($cart, true);
            $this->fail('Creating a session that needs customer details must fail without the beta');
        } catch (CustomerDetailsRequired) {
        }

        $client->assertSentCount(0);
    }

    /**
     * A logged in customer on the cart page has no addresses on the quote, so without the prefill the session would
     * be refused outside the beta.
     *
     * @magentoDataFixture Magento/Customer/_files/customer.php
     * @magentoDataFixture Magento/Customer/_files/customer_address.php
     * @magentoDataFixture Magento/Checkout/_files/quote_with_address_and_shipping_method_saved.php
     * @magentoConfigFixture current_store payment/mollie_methods_expresscomponents/beta_shipping_options 0
     */
    public function testSendsTheCustomerDefaultAddressesWhenTheQuoteHasNone(): void
    {
        $client = $this->fakeSessionRequest();
        $cart = $this->loadQuoteForCustomerWithDefaultAddresses();
        $cart->getBillingAddress()->setStreet([]);
        $cart->getShippingAddress()->setStreet([]);

        /** @var CreateSession $instance */
        $instance = $this->objectManager->create(CreateSession::class);
        $instance->execute($cart, true);

        $client->assertSent(function (PendingRequest $request): bool {
            $this->assertSame('Green str, 67', $this->extractField($request, 'billingAddress')['streetAndNumber']);
            $this->assertSame('Green str, 67', $this->extractField($request, 'shippingAddress')['streetAndNumber']);
            $this->assertSame(
                '+13468676',
                $this->extractField($request, 'billingAddress')['phone'],
                'The phone number must be sent in the international E.164 format',
            );
            $this->assertNull(
                $this->extractField($request, 'requiredCustomerDetails'),
                'With both addresses prefilled Mollie has nothing left to collect',
            );

            return true;
        });
    }

    /**
     * Mollie only accepts the shipping callback together with a shipping address it collects itself.
     *
     * @magentoDataFixture Magento/Customer/_files/customer.php
     * @magentoDataFixture Magento/Customer/_files/customer_address.php
     * @magentoDataFixture Magento/Checkout/_files/quote_with_address_and_shipping_method_saved.php
     * @magentoConfigFixture current_store payment/mollie_methods_expresscomponents/beta_shipping_options 1
     */
    public function testDoesNotSendTheShippingAddressNextToTheShippingCallback(): void
    {
        $client = $this->fakeSessionRequest();
        $cart = $this->loadQuoteForCustomerWithDefaultAddresses();

        /** @var CreateSession $instance */
        $instance = $this->objectManager->create(CreateSession::class);
        $instance->execute($cart, true);

        $client->assertSent(function (PendingRequest $request): bool {
            $this->assertNull($this->extractField($request, 'shippingAddress'));
            $this->assertNotNull($this->extractField($request, 'billingAddress'));
            $this->assertSame(['shipping-address'], $this->extractField($request, 'requiredCustomerDetails'));

            return true;
        });
    }

    /**
     * @magentoDataFixture Magento/Customer/_files/customer.php
     * @magentoDataFixture Magento/Customer/_files/customer_address.php
     * @magentoDataFixture Magento/Checkout/_files/quote_with_address_and_shipping_method_saved.php
     * @magentoConfigFixture current_store payment/mollie_methods_expresscomponents/beta_shipping_options 0
     */
    public function testPrefersTheQuoteAddressOverTheCustomerDefault(): void
    {
        $client = $this->fakeSessionRequest();
        $cart = $this->loadQuoteForCustomerWithDefaultAddresses();
        $cart->getBillingAddress()->setStreet(['Quote street 1']);
        $cart->getBillingAddress()->setTelephone('+31 6 12 34 56 78');

        /** @var CreateSession $instance */
        $instance = $this->objectManager->create(CreateSession::class);
        $instance->execute($cart, true);

        $client->assertSent(function (PendingRequest $request): bool {
            $this->assertSame('Quote street 1', $this->extractField($request, 'billingAddress')['streetAndNumber']);
            $this->assertSame('+31612345678', $this->extractField($request, 'billingAddress')['phone']);

            return true;
        });
    }

    private function loadQuoteForCustomerWithDefaultAddresses(): CartInterface
    {
        $customer = $this->objectManager->get(CustomerRepositoryInterface::class)->getById(1);
        $customer->setDefaultBilling('1');
        $customer->setDefaultShipping('1');

        $cart = $this->loadQuote();
        $cart->setCustomer($customer);
        $cart->setCustomerEmail('customer@example.com');

        return $cart;
    }

    private function fakeSessionRequest(): MollieApiClient
    {
        $client = MollieApiClient::fake([
            CreateSessionRequest::class => MockResponse::ok('session'),
        ], true);

        /** @var FakeMollieApiClient $fake */
        $fake = $this->objectManager->create(FakeMollieApiClient::class);
        $fake->setInstance($client);
        $this->objectManager->addSharedInstance($fake, \Mollie\Payment\Service\Mollie\MollieApiClient::class);

        return $client;
    }

    private function extractLines(PendingRequest $request): array
    {
        $payload = $request->payload();
        if ($payload !== null) {
            return $payload->all()['lines'] ?? [];
        }

        $body = json_decode((string)$request->createPsrRequest()->getBody(), true);

        return $body['lines'] ?? [];
    }

    private function extractField(PendingRequest $request, string $field): mixed
    {
        $payload = $request->payload();
        if ($payload !== null) {
            return $payload->all()[$field] ?? null;
        }

        $body = json_decode((string)$request->createPsrRequest()->getBody(), true);

        return $body[$field] ?? null;
    }

    private function extractAmount(PendingRequest $request): array
    {
        $payload = $request->payload();
        if ($payload !== null) {
            $amount = $payload->all()['amount'] ?? null;

            return $amount instanceof Money ? $amount->toArray() : (array)$amount;
        }

        $body = json_decode((string)$request->createPsrRequest()->getBody(), true);

        return $body['amount'] ?? [];
    }

    private function loadQuote(): CartInterface
    {
        return $this->objectManager->get(GetQuoteByReservedOrderId::class)->execute('test_order_1');
    }
}
