<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Integration\etc;

use Magento\Framework\Event\ConfigInterface;
use Mollie\Payment\Observer\SalesOrderShipmentSaveBefore\CaptureShipment;
use Mollie\Payment\Test\Integration\IntegrationTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class ShipmentEventsTest extends IntegrationTestCase
{
    /**
     * Saving a shipment track must never touch Mollie, as it would block the shipping modules that
     * print labels. See the regression that the order lock on these events caused.
     *
     * @dataProvider shipmentTrackEventsProvider
     */
    #[DataProvider('shipmentTrackEventsProvider')]
    public function testTheShipmentTrackEventsHaveNoMollieObservers(string $event): void
    {
        $this->assertSame([], $this->getMollieObservers($event));
    }

    public function testTheShipmentIsCapturedBeforeItIsSaved(): void
    {
        $this->assertSame(
            [CaptureShipment::class],
            $this->getMollieObservers('sales_order_shipment_save_before')
        );
    }

    /**
     * @return array<int, array<int, string>>
     */
    public static function shipmentTrackEventsProvider(): array
    {
        return [
            ['sales_order_shipment_track_save_before'],
            ['sales_order_shipment_track_save_after'],
            ['sales_order_shipment_save_after'],
        ];
    }

    /**
     * @return array<int, string>
     */
    private function getMollieObservers(string $event): array
    {
        /** @var ConfigInterface $eventConfig */
        $eventConfig = $this->objectManager->get(ConfigInterface::class);

        $instances = array_map(
            static fn (array $observer): string => ltrim($observer['instance'] ?? '', '\\'),
            $eventConfig->getObservers($event)
        );

        return array_values(array_filter($instances, static fn (string $instance): bool => str_contains($instance, 'Mollie')));
    }
}
