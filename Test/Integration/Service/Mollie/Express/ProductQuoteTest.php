<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Integration\Service\Mollie\Express;

use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Model\ResourceModel\Quote\CollectionFactory;
use Mollie\Payment\Service\Mollie\Express\ProductBuyRequest;
use Mollie\Payment\Service\Mollie\Express\ProductQuote;
use Mollie\Payment\Test\Integration\IntegrationTestCase;

/**
 * @magentoAppArea frontend
 */
class ProductQuoteTest extends IntegrationTestCase
{
    /**
     * @magentoDataFixture Magento/Catalog/_files/products.php
     */
    public function testBuildsAQuoteWithTheProductAndItsTotals(): void
    {
        $quote = $this->createQuote(['product' => '1', 'qty' => '1']);

        $items = $quote->getItems();
        $this->assertCount(1, $items);
        $this->assertSame('simple', $items[0]->getSku());
        $this->assertEquals(10.0, (float)$items[0]->getPriceInclTax());
        $this->assertNotEmpty($quote->getQuoteCurrencyCode());
    }

    /**
     * Saving a quote for every visitor of a product page is what this class exists to prevent.
     *
     * @magentoDataFixture Magento/Catalog/_files/products.php
     */
    public function testDoesNotSaveTheQuote(): void
    {
        $quotesBefore = $this->countQuotes();

        $quote = $this->createQuote(['product' => '1', 'qty' => '1']);

        $this->assertNull($quote->getId());
        $this->assertSame($quotesBefore, $this->countQuotes());
    }

    public function testRefusesABuyRequestWithoutAProduct(): void
    {
        $this->expectException(LocalizedException::class);

        ProductBuyRequest::fromRequestParams(['qty' => '1']);
    }

    /**
     * The form also posts the form key and other fields that have no place in the Mollie metadata.
     */
    public function testKeepsOnlyTheFieldsThatDescribeTheProduct(): void
    {
        $buyRequest = ProductBuyRequest::fromRequestParams([
            'product' => '1',
            'qty' => '2',
            'super_attribute' => ['93' => '52'],
            'form_key' => 'secret',
            'related_product' => '',
        ]);

        $this->assertSame(
            ['product' => '1', 'qty' => '2', 'super_attribute' => ['93' => '52']],
            $buyRequest->toArray(),
        );
    }

    private function createQuote(array $params): \Magento\Quote\Api\Data\CartInterface
    {
        return $this->objectManager->create(ProductQuote::class)
            ->create(ProductBuyRequest::fromRequestParams($params), 1, null);
    }

    private function countQuotes(): int
    {
        return $this->objectManager->create(CollectionFactory::class)->create()->getSize();
    }
}
