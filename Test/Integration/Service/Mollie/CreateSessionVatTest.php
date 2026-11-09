<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Integration\Service\Mollie;

use Magento\Quote\Api\Data\CartInterface;
use Magento\TestFramework\Quote\Model\GetQuoteByReservedOrderId;
use Mollie\Api\Fake\MockResponse;
use Mollie\Api\Http\PendingRequest;
use Mollie\Api\MollieApiClient;
use Mollie\Payment\Service\Mollie\Api\CreateSessionRequest;
use Mollie\Payment\Service\Mollie\CreateSession;
use Mollie\Payment\Test\Fakes\Service\Mollie\FakeMollieApiClient;
use Mollie\Payment\Test\Integration\IntegrationTestCase;

/**
 * Separate from CreateSessionTest: Magento caches tax rates in shared instances, and the app is only reset between
 * test classes and after isolated tests.
 *
 * @magentoAppArea frontend
 * @magentoAppIsolation enabled
 * @see https://github.com/mollie/magento2/issues/1129
 */
class CreateSessionVatTest extends IntegrationTestCase
{
    private const VAT_LINE_TYPES = ['physical', 'digital', 'shipping_fee'];
    private const TAXABLE_GOODS_CLASS_ID = 2;

    /**
     * @magentoDataFixture Magento/Tax/_files/tax_rule_region_1_al.php
     * @magentoDataFixture Magento/Checkout/_files/quote_with_address_and_shipping_method_saved.php
     * @magentoConfigFixture current_store tax/classes/shipping_tax_class 2
     */
    public function testSendsTheConfiguredShippingVatRateOnTheCheckoutPath(): void
    {
        $client = $this->fakeSessionRequest();

        $this->objectManager->create(CreateSession::class)->execute($this->loadQuote(), false);

        $client->assertSent(function (PendingRequest $request): bool {
            $shippingLines = array_values(array_filter(
                $this->extractLines($request),
                static fn (array $line): bool => $line['type'] === 'shipping_fee',
            ));

            $this->assertCount(1, $shippingLines);
            $this->assertSame('7.50', $shippingLines[0]['vatRate']);
            $this->assertNotSame('0.00', $shippingLines[0]['vatAmount']['value']);

            return true;
        });
    }

    /**
     * Mollie rejects a line when vatAmount differs from totalAmount × (vatRate / (100 + vatRate)).
     *
     * @magentoDataFixture Magento/Tax/_files/tax_rule_region_1_al.php
     * @magentoDataFixture Magento/Checkout/_files/quote_with_address_and_shipping_method_saved.php
     * @magentoConfigFixture current_store tax/classes/shipping_tax_class 2
     */
    public function testSendsAVatAmountThatMatchesTheMollieFormulaOnEveryProductAndShippingLine(): void
    {
        $client = $this->fakeSessionRequest();

        $this->objectManager->create(CreateSession::class)->execute($this->loadQuote(), false);

        $client->assertSent(function (PendingRequest $request): bool {
            $lines = array_filter(
                $this->extractLines($request),
                static fn (array $line): bool => in_array($line['type'], self::VAT_LINE_TYPES, true),
            );

            $this->assertNotEmpty($lines);
            foreach ($lines as $line) {
                $this->assertSame(
                    $this->expectedVatAmount((float)$line['totalAmount']['value'], (float)$line['vatRate']),
                    $line['vatAmount']['value'],
                    'Mollie would reject line "' . $line['description'] . '"',
                );
            }

            return true;
        });
    }

    /**
     * @magentoDataFixture Magento/Tax/_files/tax_rule_region_1_al.php
     * @magentoDataFixture Magento/Checkout/_files/quote_with_address_and_shipping_method_saved.php
     */
    public function testSendsTheProductVatRateOnTheExpressPath(): void
    {
        $client = $this->fakeSessionRequest();
        $cart = $this->givenTaxableProducts($this->loadQuote());

        $this->objectManager->create(CreateSession::class)->execute($cart, true);

        $client->assertSent(function (PendingRequest $request): bool {
            $lines = $this->extractLines($request);

            $this->assertNotEmpty($lines);
            foreach ($lines as $line) {
                $this->assertSame('7.50', $line['vatRate']);
                $this->assertSame(
                    $this->expectedVatAmount((float)$line['totalAmount']['value'], (float)$line['vatRate']),
                    $line['vatAmount']['value'],
                );
            }

            return true;
        });
    }

    private function givenTaxableProducts(CartInterface $cart): CartInterface
    {
        foreach ($cart->getAllItems() as $item) {
            $item->getProduct()->setTaxClassId(self::TAXABLE_GOODS_CLASS_ID);
        }
        $cart->setTotalsCollectedFlag(false)->collectTotals();

        return $cart;
    }

    private function expectedVatAmount(float $totalAmount, float $vatRate): string
    {
        return number_format(round($totalAmount * $vatRate / (100 + $vatRate), 2), 2, '.', '');
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

    /**
     * @return array<int, array<string, mixed>>
     */
    private function extractLines(PendingRequest $request): array
    {
        $payload = $request->payload();
        if ($payload !== null) {
            return $payload->all()['lines'] ?? [];
        }

        return json_decode((string)$request->createPsrRequest()->getBody(), true)['lines'] ?? [];
    }

    private function loadQuote(): CartInterface
    {
        return $this->objectManager->get(GetQuoteByReservedOrderId::class)->execute('test_order_1');
    }
}
