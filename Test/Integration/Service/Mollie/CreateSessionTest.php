<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Integration\Service\Mollie;

use Magento\Quote\Api\CartTotalRepositoryInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\TestFramework\Quote\Model\GetQuoteByReservedOrderId;
use Mollie\Api\Fake\MockResponse;
use Mollie\Api\Http\Data\Money;
use Mollie\Api\Http\PendingRequest;
use Mollie\Api\MollieApiClient;
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
