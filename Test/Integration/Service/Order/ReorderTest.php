<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Test\Integration\Service\Order;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Model\Quote;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderItemInterface;
use Mollie\Payment\Service\Order\CopyOriginalOrderItemData;
use Mollie\Payment\Test\Integration\IntegrationTestCase;

class ReorderTest extends IntegrationTestCase
{
    /**
     * @var CopyOriginalOrderItemData
     */
    private $instance;

    protected function setUpWithoutVoid()
    {
        $this->instance = $this->objectManager->create(CopyOriginalOrderItemData::class);
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testCopiesCustomPriceFromOrderItemToQuoteItem()
    {
        $order = $this->loadOrderById('100000001');
        $quote = $this->createQuoteFromOrder($order);

        $this->instance->execute($order, $quote);

        $quoteItem = $this->getFirstVisibleItem($quote);
        $orderItem = $this->getFirstItem($order);

        $this->assertEquals(
            $orderItem->getPrice(),
            $quoteItem->getCustomPrice(),
            'The custom price on the quote item should match the original order item price'
        );
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testCustomPriceIsSetOnQuoteItem()
    {
        $order = $this->loadOrderById('100000001');
        $quote = $this->createQuoteFromOrder($order);

        // Before: quote item should not have a custom price
        $quoteItemBefore = $this->getFirstVisibleItem($quote);
        $this->assertNull($quoteItemBefore->getCustomPrice(), 'Quote item should not have a custom price before copy');

        $this->instance->execute($order, $quote);

        $quoteItemAfter = $this->getFirstVisibleItem($quote);
        $orderItem = $this->getFirstItem($order);

        $this->assertNotNull($quoteItemAfter->getCustomPrice(), 'Quote item should have a custom price after copy');
        $this->assertEquals(
            $orderItem->getPrice(),
            $quoteItemAfter->getCustomPrice(),
            'The custom price should be set to the original order item price'
        );
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testSkipsChildItems()
    {
        $order = $this->loadOrderById('100000001');
        $parentItem = $this->getFirstItem($order);

        $childItem = $this->objectManager->create(OrderItemInterface::class);
        $childItem->setProductId($parentItem->getProductId());
        $childItem->setSku($parentItem->getSku());
        $childItem->setParentItemId($parentItem->getItemId());
        $childItem->setPrice(999);
        $order->addItem($childItem);

        $quote = $this->createQuoteFromOrder($order);

        $this->instance->execute($order, $quote);

        $quoteItem = $this->getFirstVisibleItem($quote);

        $this->assertEquals(
            $parentItem->getPrice(),
            $quoteItem->getCustomPrice(),
            'Only parent order items should be matched to quote items'
        );
    }

    private function createQuoteFromOrder(OrderInterface $order): CartInterface
    {
        $productRepository = $this->objectManager->get(ProductRepositoryInterface::class);

        /** @var Quote $quote */
        $quote = $this->objectManager->create(Quote::class);
        $quote->setStoreId($order->getStoreId());

        foreach ($order->getItems() as $orderItem) {
            if ($orderItem->getParentItemId()) {
                continue;
            }

            $product = $productRepository->getById($orderItem->getProductId());

            try {
                $quote->addProduct($product, $orderItem->getQtyOrdered());
            } catch (LocalizedException $e) {
                // This only happens in the "with-replacements" variants
                $this->markTestSkipped('Product is not available: ' . $e->getMessage());
            }
        }

        $quote->collectTotals();

        return $quote;
    }

    private function getFirstItem(OrderInterface $order)
    {
        foreach ($order->getItems() as $item) {
            if (!$item->getParentItemId()) {
                return $item;
            }
        }

        return null;
    }

    private function getFirstVisibleItem(CartInterface $quote)
    {
        $items = $quote->getAllVisibleItems();

        return reset($items);
    }
}
