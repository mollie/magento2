<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Integration\Service\Order;

use Magento\CatalogInventory\Model\StockRegistryStorage;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Module\Manager;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\InventoryReservationsApi\Model\GetReservationsQuantityInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Item as OrderItem;
use Mollie\Payment\Service\Order\Uncancel;
use Mollie\Payment\Service\Order\Uncancel\OrderReservation;
use Mollie\Payment\Test\Fakes\Framework\Module\ModuleManagerFake;
use Mollie\Payment\Test\Fakes\Model\OrderRepositoryFake;
use Mollie\Payment\Test\Integration\IntegrationTestCase;

class UncancelTest extends IntegrationTestCase
{
    private const DEFAULT_STOCK_ID = 1;
    private const ORDERED_QUANTITY = 2.0;
    private const INVENTORY_SALES_API_MODULE = 'Magento_InventorySalesApi';

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testSetsTheOrderTotals(): void
    {
        $order = $this->cancelOrder($this->loadOrder('100000001'));

        $this->assertEquals(100, $order->getSubtotalCanceled());
        $this->assertEquals(100, $order->getTotalCanceled());

        /** @var Uncancel $instance */
        $instance = $this->objectManager->create(Uncancel::class);
        $instance->execute($order);

        $this->assertEquals(0, $order->getSubtotalCanceled());
        $this->assertEquals(0, $order->getTotalCanceled());
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testItReservesTheCanceledQuantityOfASimpleProductAgain(): void
    {
        $this->skipWithoutInventoryReservations();

        $order = $this->cancelOrder($this->loadOrder('100000001'));

        $this->objectManager->create(Uncancel::class)->execute($order);

        $this->assertSame(0.0, $this->getReservedQuantity('simple'));
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order_configurable_product.php
     */
    public function testItReservesTheCanceledQuantityOfAConfigurableProductOnlyOnce(): void
    {
        $this->skipWithoutInventoryReservations();

        $order = $this->cancelOrder($this->withConfigurableChildSku($this->loadOrder('100000001')));

        $this->objectManager->create(Uncancel::class)->execute($order);

        $this->assertSame(0.0, $this->getReservedQuantity('simple_10'));
    }

    /**
     * @magentoDataFixture Magento/Bundle/_files/order_with_bundle_shipped_together.php
     */
    public function testItReservesTheCanceledQuantityOfTheBundleSelectionsAgain(): void
    {
        $this->skipWithoutInventoryReservations();

        $order = $this->cancelOrder($this->withBundleSelectionAttributes($this->loadOrder('100000001')));

        $this->objectManager->create(Uncancel::class)->execute($order);

        $this->assertSame(0.0, $this->getReservedQuantity('simple'));
        $this->assertSame(0.0, $this->getReservedQuantity('custom-design-simple-product'));
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testItAddsTheOrderIncrementIdToTheUncancelReservation(): void
    {
        $this->skipWithoutInventoryReservations();

        $order = $this->cancelOrder($this->loadOrder('100000001'));

        $this->objectManager->create(Uncancel::class)->execute($order);

        $this->assertSame('100000001', $this->getUncancelReservationMetadata('simple')['object_increment_id'] ?? null);
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testItRestoresTheStockOnlyOnceWhenAnOutdatedCopyOfTheOrderIsUncanceledAgain(): void
    {
        $this->skipWithoutInventoryReservations();

        $order = $this->cancelOrder($this->loadOrder('100000001'));
        $outdatedCopy = $this->loadOrder('100000001');

        $this->objectManager->create(Uncancel::class)->execute($order);
        $this->objectManager->create(Uncancel::class)->execute($outdatedCopy);

        $this->assertSame(0.0, $this->getReservedQuantity('simple'));
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testItLeavesAnOrderThatIsNotCanceledInTheDatabaseUntouched(): void
    {
        $this->skipWithoutInventoryReservations();

        $order = $this->loadOrder('100000001');
        $order->cancel();

        $this->objectManager->create(Uncancel::class)->execute($order);

        $this->assertSame(Order::STATE_CANCELED, $order->getState());
        $this->assertSame(0, $this->countUncancelReservations('simple'));
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testItDeductsTheLegacyStockWhenInventoryReservationsAreDisabled(): void
    {
        $order = $this->cancelOrder($this->loadOrder('100000001'));
        $initialQuantity = $this->getLegacyStockQuantity('simple');

        $this->createUncancelWithoutInventoryReservations()->execute($order);

        $this->assertSame($initialQuantity - self::ORDERED_QUANTITY, $this->getLegacyStockQuantity('simple'));
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testItPlacesNoReservationWhenInventoryReservationsAreDisabled(): void
    {
        $this->skipWithoutInventoryReservations();

        $order = $this->cancelOrder($this->loadOrder('100000001'));

        $this->createUncancelWithoutInventoryReservations()->execute($order);

        $this->assertSame(0, $this->countUncancelReservations('simple'));
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testItLeavesTheLegacyStockAloneWhenInventoryReservationsAreEnabled(): void
    {
        $this->skipWithoutInventoryReservations();

        $order = $this->cancelOrder($this->loadOrder('100000001'));
        $initialQuantity = $this->getLegacyStockQuantity('simple');

        $this->objectManager->create(Uncancel::class)->execute($order);

        $this->assertSame($initialQuantity, $this->getLegacyStockQuantity('simple'));
    }

    /**
     * @magentoDbIsolation disabled
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testItRollsBackTheLegacyStockWhenTheOrderCannotBeSaved(): void
    {
        $lastReservationId = $this->getLastReservationId();

        try {
            $order = $this->cancelOrder($this->loadOrder('100000001'));
            $initialQuantity = $this->getLegacyStockQuantity('simple');

            $exception = $this->uncancelWithFailingOrderSave($order);

            $this->assertInstanceOf(CouldNotSaveException::class, $exception);
            $this->assertSame($initialQuantity, $this->getLegacyStockQuantity('simple'));
            $this->assertSame(Order::STATE_CANCELED, $this->loadOrder('100000001')->getState());
        } finally {
            $this->removeReservationsAfter($lastReservationId);
        }
    }

    private function uncancelWithFailingOrderSave(OrderInterface $order): ?CouldNotSaveException
    {
        $orderRepository = $this->objectManager->create(OrderRepositoryFake::class);
        $orderRepository->givenSaveFails();

        try {
            $this->createUncancelWithoutInventoryReservations($orderRepository)->execute($order);
        } catch (CouldNotSaveException $exception) {
            return $exception;
        }

        return null;
    }

    private function createUncancelWithoutInventoryReservations(?OrderRepositoryFake $orderRepository = null): Uncancel
    {
        $this->objectManager->get(StockRegistryStorage::class)->clean();
        $moduleManager = $this->objectManager->create(ModuleManagerFake::class);
        $moduleManager->givenModuleIsDisabled(self::INVENTORY_SALES_API_MODULE);

        return $this->objectManager->create(Uncancel::class, array_filter([
            'moduleManager' => $moduleManager,
            'orderRepository' => $orderRepository,
        ]));
    }

    private function cancelOrder(OrderInterface $order): OrderInterface
    {
        $order->cancel();
        $this->objectManager->get(OrderRepositoryInterface::class)->save($order);

        return $this->loadOrder((string)$order->getIncrementId());
    }

    private function withConfigurableChildSku(OrderInterface $order): OrderInterface
    {
        $childSku = $this->getChildItem($order)->getProduct()->getSku();
        $this->getChildItem($order)->setSku($childSku);
        $this->getParentItem($order)->setSku($childSku)->setProductOptions(['simple_sku' => $childSku]);
        $this->objectManager->get(OrderRepositoryInterface::class)->save($order);

        return $this->loadOrder((string)$order->getIncrementId());
    }

    private function withBundleSelectionAttributes(OrderInterface $order): OrderInterface
    {
        $selectionAttributes = $this->objectManager->get(Json::class)->serialize(['qty' => 1]);
        foreach ($order->getAllItems() as $item) {
            if ($item->getParentItem() !== null) {
                $item->setProductOptions(['bundle_selection_attributes' => $selectionAttributes]);
            }
        }
        $this->objectManager->get(OrderRepositoryInterface::class)->save($order);

        return $this->loadOrder((string)$order->getIncrementId());
    }

    private function getParentItem(OrderInterface $order): OrderItem
    {
        return current(array_filter($order->getAllItems(), fn ($item): bool => $item->getParentItem() === null));
    }

    private function getChildItem(OrderInterface $order): OrderItem
    {
        return current(array_filter($order->getAllItems(), fn ($item): bool => $item->getParentItem() !== null));
    }

    private function getReservedQuantity(string $sku): float
    {
        return (float)$this->objectManager->get(GetReservationsQuantityInterface::class)
            ->execute($sku, self::DEFAULT_STOCK_ID);
    }

    private function selectUncancelReservations(string $sku): Select
    {
        $connection = $this->objectManager->get(ResourceConnection::class)->getConnection();

        return $connection->select()
            ->from($connection->getTableName('inventory_reservation'), ['metadata'])
            ->where('sku = ?', $sku)
            ->where('metadata LIKE ?', '%"event_type":"' . OrderReservation::EVENT_ORDER_UNCANCELED . '"%');
    }

    private function skipWithoutInventoryReservations(): void
    {
        if (!$this->isInventoryReservationsInstalled()) {
            $this->markTestSkipped('Module ' . self::INVENTORY_SALES_API_MODULE . ' is not enabled');
        }
    }

    private function isInventoryReservationsInstalled(): bool
    {
        return $this->objectManager->get(Manager::class)->isEnabled(self::INVENTORY_SALES_API_MODULE);
    }

    private function getLastReservationId(): int
    {
        if (!$this->isInventoryReservationsInstalled()) {
            return 0;
        }

        $connection = $this->objectManager->get(ResourceConnection::class)->getConnection();

        return (int)$connection->fetchOne(
            $connection->select()->from($connection->getTableName('inventory_reservation'), ['MAX(reservation_id)']),
        );
    }

    private function removeReservationsAfter(int $reservationId): void
    {
        if (!$this->isInventoryReservationsInstalled()) {
            return;
        }

        $connection = $this->objectManager->get(ResourceConnection::class)->getConnection();
        $connection->delete($connection->getTableName('inventory_reservation'), ['reservation_id > ?' => $reservationId]);
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

    private function countUncancelReservations(string $sku): int
    {
        $connection = $this->objectManager->get(ResourceConnection::class)->getConnection();

        return (int)$connection->fetchOne(
            $this->selectUncancelReservations($sku)->reset(Select::COLUMNS)->columns(['COUNT(*)']),
        );
    }

    private function getUncancelReservationMetadata(string $sku): array
    {
        $connection = $this->objectManager->get(ResourceConnection::class)->getConnection();
        $metadata = $connection->fetchOne(
            $this->selectUncancelReservations($sku)->order('reservation_id DESC')->limit(1),
        );

        return $this->objectManager->get(Json::class)->unserialize((string)$metadata);
    }
}
