<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Service\Mollie\Express;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Directory\Model\Currency;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\DataObjectFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Filter\LocalizedToNormalized;
use Magento\Framework\Locale\ResolverInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\QuoteFactory;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Builds the quote for an express payment on the product page in memory. Saving it would create a quote for every
 * visitor of a product page, and it would mix with the shopping cart of the customer.
 */
class ProductQuote
{
    public function __construct(
        private readonly QuoteFactory $quoteFactory,
        private readonly ProductRepositoryInterface $productRepository,
        private readonly CustomerRepositoryInterface $customerRepository,
        private readonly StoreManagerInterface $storeManager,
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly DataObjectFactory $dataObjectFactory,
        private readonly ResolverInterface $localeResolver,
    ) {}

    /**
     * @throws LocalizedException When the product cannot be bought with this buy request, like a configurable
     *                            product without its options.
     */
    public function create(ProductBuyRequest $buyRequest, int $storeId, ?int $customerId): CartInterface
    {
        /** @var Store $store */
        $store = $this->storeManager->getStore($storeId);

        $quote = $this->quoteFactory->create();
        $quote->setStore($store);
        $this->setCurrencies($quote, $store);
        $this->assignCustomer($quote, $customerId);
        $this->addProduct($quote, $buyRequest, $storeId);

        $quote->getBillingAddress();
        $quote->getShippingAddress();
        $quote->collectTotals();

        $quote->setItems($quote->getAllVisibleItems());

        return $quote;
    }

    /**
     * Magento sets these when it saves a quote, which this quote never is.
     */
    private function setCurrencies(Quote $quote, Store $store): void
    {
        $globalCurrencyCode = (string)$this->scopeConfig->getValue(Currency::XML_PATH_CURRENCY_BASE);
        $baseCurrency = $store->getBaseCurrency();
        $quoteCurrency = $store->getCurrentCurrency();

        $quote->setGlobalCurrencyCode($globalCurrencyCode);
        $quote->setBaseCurrencyCode($baseCurrency->getCode());
        $quote->setStoreCurrencyCode($baseCurrency->getCode());
        $quote->setQuoteCurrencyCode($quoteCurrency->getCode());
        $quote->setBaseToGlobalRate($baseCurrency->getRate($globalCurrencyCode));
        $quote->setBaseToQuoteRate($baseCurrency->getRate($quoteCurrency));
    }

    private function assignCustomer(Quote $quote, ?int $customerId): void
    {
        if ($customerId === null) {
            return;
        }

        $quote->assignCustomer($this->customerRepository->getById($customerId));
    }

    /**
     * @throws LocalizedException
     */
    private function addProduct(Quote $quote, ProductBuyRequest $buyRequest, int $storeId): void
    {
        $result = $quote->addProduct(
            $this->getProduct($buyRequest->getProductId(), $storeId),
            $this->dataObjectFactory->create(['data' => $this->normalizeQty($buyRequest->toArray())]),
        );

        if (is_string($result)) {
            throw new LocalizedException(__($result));
        }
    }

    /**
     * @throws LocalizedException
     */
    private function getProduct(int $productId, int $storeId): Product
    {
        try {
            /** @var Product $product */
            $product = $this->productRepository->getById($productId, false, $storeId);

            return $product;
        } catch (NoSuchEntityException $exception) {
            throw new LocalizedException(__('The requested product could not be found.'), $exception);
        }
    }

    /**
     * @param array<string, mixed> $fields
     * @return array<string, mixed>
     */
    private function normalizeQty(array $fields): array
    {
        if (!isset($fields[ProductBuyRequest::QTY])) {
            return $fields;
        }

        $filter = new LocalizedToNormalized(['locale' => $this->localeResolver->getLocale()]);
        $fields[ProductBuyRequest::QTY] = $filter->filter((string)$fields[ProductBuyRequest::QTY]);

        return $fields;
    }
}
