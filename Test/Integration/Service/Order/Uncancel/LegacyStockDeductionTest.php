<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Integration\Service\Order\Uncancel;

use Magento\CatalogInventory\Model\StockRegistryStorage;
use Magento\Framework\App\ResourceConnection;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order\Item as OrderItem;
use Mollie\Payment\Service\Order\Uncancel\LegacyStockDeduction;
use Mollie\Payment\Test\Integration\IntegrationTestCase;

class LegacyStockDeductionTest extends IntegrationTestCase
{
    private const ORDERED_QUANTITY = 2.0;

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testItDeductsTheOrderedQuantityFromTheLegacyStock(): void
    {
        $order = $this->loadOrder('100000001');
        $initialQuantity = $this->getLegacyStockQuantity('simple');

        $this->deductAllItems($order);

        $this->assertSame($initialQuantity - self::ORDERED_QUANTITY, $this->getLegacyStockQuantity('simple'));
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     * @magentoConfigFixture default_store cataloginventory/options/can_back_in_stock 0
     */
    public function testItLeavesTheLegacyStockAloneWhenCanceledItemsDoNotGoBackInStock(): void
    {
        $order = $this->loadOrder('100000001');
        $initialQuantity = $this->getLegacyStockQuantity('simple');

        $this->deductAllItems($order);

        $this->assertSame($initialQuantity, $this->getLegacyStockQuantity('simple'));
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order_configurable_product.php
     */
    public function testItDeductsTheChildOfAConfigurableProductOnlyOnce(): void
    {
        $order = $this->loadOrder('100000001');
        $initialQuantity = $this->getLegacyStockQuantity('simple_10');

        $this->deductAllItems($order);

        $this->assertSame($initialQuantity - self::ORDERED_QUANTITY, $this->getLegacyStockQuantity('simple_10'));
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testItDoesNotDeductTheInvoicedQuantity(): void
    {
        $order = $this->loadOrder('100000001');
        $this->getFirstItem($order)->setQtyInvoiced(1);
        $initialQuantity = $this->getLegacyStockQuantity('simple');

        $this->deductAllItems($order);

        $this->assertSame($initialQuantity - 1, $this->getLegacyStockQuantity('simple'));
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testItDoesNotDeductTheShippedQuantityWhenMoreIsShippedThanInvoiced(): void
    {
        $order = $this->loadOrder('100000001');
        $this->getFirstItem($order)->setQtyShipped(1)->setQtyInvoiced(0);
        $initialQuantity = $this->getLegacyStockQuantity('simple');

        $this->deductAllItems($order);

        $this->assertSame($initialQuantity - 1, $this->getLegacyStockQuantity('simple'));
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testItLeavesProductsWithoutStockManagementAlone(): void
    {
        $order = $this->loadOrder('100000001');
        $this->disableStockManagement('simple');
        $initialQuantity = $this->getLegacyStockQuantity('simple');

        $this->deductAllItems($order);

        $this->assertSame($initialQuantity, $this->getLegacyStockQuantity('simple'));
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testItSkipsProductsWithoutAStockItem(): void
    {
        $order = $this->loadOrder('100000001');
        $this->removeStockItem('simple');

        $this->deductAllItems($order);

        $this->assertSame(0, $this->countStockItems('simple'));
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testItSkipsProductTypesThatHaveNoQuantity(): void
    {
        $order = $this->loadOrder('100000001');
        $this->getFirstItem($order)->setProductType('grouped');
        $initialQuantity = $this->getLegacyStockQuantity('simple');

        $this->deductAllItems($order);

        $this->assertSame($initialQuantity, $this->getLegacyStockQuantity('simple'));
    }

    /**
     * @magentoDataFixture Magento/Bundle/_files/order_with_bundle_shipped_together.php
     */
    public function testItDeductsEachBundleSelection(): void
    {
        $order = $this->loadOrder('100000001');
        $initialSimpleQuantity = $this->getLegacyStockQuantity('simple');
        $initialCustomDesignQuantity = $this->getLegacyStockQuantity('custom-design-simple-product');

        $this->deductAllItems($order);

        $this->assertSame($initialSimpleQuantity - 1, $this->getLegacyStockQuantity('simple'));
        $this->assertSame(
            $initialCustomDesignQuantity - 1,
            $this->getLegacyStockQuantity('custom-design-simple-product'),
        );
    }

    private function getFirstItem(OrderInterface $order): OrderItem
    {
        return current($order->getAllItems());
    }

    private function disableStockManagement(string $sku): void
    {
        $connection = $this->objectManager->get(ResourceConnection::class)->getConnection();
        $connection->update(
            $connection->getTableName('cataloginventory_stock_item'),
            ['use_config_manage_stock' => 0, 'manage_stock' => 0],
            ['product_id = ?' => $this->getProductId($sku)],
        );
    }

    private function removeStockItem(string $sku): void
    {
        $connection = $this->objectManager->get(ResourceConnection::class)->getConnection();
        $connection->delete(
            $connection->getTableName('cataloginventory_stock_item'),
            ['product_id = ?' => $this->getProductId($sku)],
        );
    }

    private function countStockItems(string $sku): int
    {
        $connection = $this->objectManager->get(ResourceConnection::class)->getConnection();

        return (int)$connection->fetchOne(
            $connection->select()
                ->from($connection->getTableName('cataloginventory_stock_item'), ['COUNT(*)'])
                ->where('product_id = ?', $this->getProductId($sku)),
        );
    }

    private function getProductId(string $sku): int
    {
        $connection = $this->objectManager->get(ResourceConnection::class)->getConnection();

        return (int)$connection->fetchOne(
            $connection->select()
                ->from($connection->getTableName('catalog_product_entity'), ['entity_id'])
                ->where('sku = ?', $sku),
        );
    }

    private function deductAllItems(OrderInterface $order): void
    {
        $this->objectManager->get(StockRegistryStorage::class)->clean();
        $instance = $this->objectManager->create(LegacyStockDeduction::class);

        foreach ($order->getAllItems() as $item) {
            $instance->execute($item);
        }
    }

    private function getLegacyStockQuantity(string $sku): float
    {
        $connection = $this->objectManager->get(ResourceConnection::class)->getConnection();

        return (float)$connection->fetchOne(
            $connection->select()
                ->from(['stock' => $connection->getTableName('cataloginventory_stock_item')], ['qty'])
                ->join(
                    ['product' => $connection->getTableName('catalog_product_entity')],
                    'product.entity_id = stock.product_id',
                    [],
                )
                ->where('product.sku = ?', $sku),
        );
    }
}
