<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Integration\Observer\AdminhtmlSalesOrderCreateProcessItemBefore;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Event\Observer;
use Mollie\Payment\Observer\AdminhtmlSalesOrderCreateProcessItemBefore\SetPurchaseTypeOnCreateOrderItem;
use Mollie\Payment\Test\Fakes\Catalog\ProductRepositoryFake;
use Mollie\Payment\Test\Integration\IntegrationTestCase;

class SetPurchaseTypeOnCreateOrderItemTest extends IntegrationTestCase
{
    private const QUOTE_ITEM_ID = 15;
    private const SUBSCRIPTION_PRODUCT_ID = 15;
    private const REGULAR_PRODUCT_ID = 16;
    private const ACTION_SAVE = 'save';
    private const ACTION_LOAD_BLOCK = 'loadBlock';

    private ?ProductRepositoryFake $productRepository = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->productRepository = new ProductRepositoryFake();
    }

    public function testDoesNotLoadQuoteItemIdsAsProductsWhenSavingTheOrder(): void
    {
        $request = $this->createRequest(self::ACTION_SAVE, [self::QUOTE_ITEM_ID => ['qty' => '1']]);

        $this->executeObserver($request);

        $this->assertSame([], $this->productRepository->getRequestedProductIds());
    }

    public function testDoesNotSetThePurchaseTypeOnQuoteItemsWhenSavingTheOrder(): void
    {
        $this->productRepository->withProduct($this->createProduct(self::SUBSCRIPTION_PRODUCT_ID, true));
        $request = $this->createRequest(self::ACTION_SAVE, [self::QUOTE_ITEM_ID => ['qty' => '1']]);

        $this->executeObserver($request);

        $this->assertSame([self::QUOTE_ITEM_ID => ['qty' => '1']], $request->getPost('item'));
    }

    public function testSetsThePurchaseTypeToOnetimeWhenAddingASubscriptionProduct(): void
    {
        $this->productRepository->withProduct($this->createProduct(self::SUBSCRIPTION_PRODUCT_ID, true));
        $request = $this->createRequest(self::ACTION_LOAD_BLOCK, [self::SUBSCRIPTION_PRODUCT_ID => ['qty' => '1']]);

        $this->executeObserver($request);

        $this->assertSame(
            [self::SUBSCRIPTION_PRODUCT_ID => ['qty' => '1', 'purchase' => 'onetime']],
            $request->getPost('item')
        );
    }

    public function testLeavesRegularProductsUntouchedWhenAddingThem(): void
    {
        $this->productRepository->withProduct($this->createProduct(self::REGULAR_PRODUCT_ID, false));
        $request = $this->createRequest(self::ACTION_LOAD_BLOCK, [self::REGULAR_PRODUCT_ID => ['qty' => '1']]);

        $this->executeObserver($request);

        $this->assertSame([self::REGULAR_PRODUCT_ID => ['qty' => '1']], $request->getPost('item'));
    }

    public function testDoesNotLoadProductsWhenUpdatingExistingItems(): void
    {
        $request = $this->createRequest(self::ACTION_LOAD_BLOCK, [self::QUOTE_ITEM_ID => ['qty' => '2']]);
        $request->setPostValue('update_items', '1');

        $this->executeObserver($request);

        $this->assertSame([], $this->productRepository->getRequestedProductIds());
    }

    private function executeObserver(Http $request): void
    {
        $observer = $this->objectManager->create(Observer::class);
        $observer->setData('request_model', $request);

        $this->objectManager->create(SetPurchaseTypeOnCreateOrderItem::class, [
            'productRepository' => $this->productRepository,
        ])->execute($observer);
    }

    private function createRequest(string $actionName, array $items): Http
    {
        /** @var Http $request */
        $request = $this->objectManager->create(Http::class);
        $request->setActionName($actionName);
        $request->setPostValue('item', $items);

        return $request;
    }

    private function createProduct(int $id, bool $isSubscriptionProduct): ProductInterface
    {
        /** @var ProductInterface $product */
        $product = $this->objectManager->create(ProductInterface::class);
        $product->setId($id);
        $product->setData('mollie_subscription_product', $isSubscriptionProduct ? '1' : '0');

        return $product;
    }
}
