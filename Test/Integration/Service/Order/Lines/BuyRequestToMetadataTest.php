<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Integration\Service\Order\Lines;

use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderItemInterface;
use Mollie\Payment\Service\Order\Lines\BuyRequestToMetadata;
use Mollie\Payment\Test\Integration\IntegrationTestCase;

class BuyRequestToMetadataTest extends IntegrationTestCase
{
    public function testDoesNothingWhenNoOrderItemIsPresent(): void
    {
        /** @var BuyRequestToMetadata $instance */
        $instance = $this->objectManager->create(BuyRequestToMetadata::class);

        $result = $instance->process(['empty' => true], $this->objectManager->create(OrderInterface::class));

        $this->assertEquals(['empty' => true], $result);
    }

    public function testDoesNothingWhenThereIsNoBuyRequestAvailable(): void
    {
        /** @var BuyRequestToMetadata $instance */
        $instance = $this->objectManager->create(BuyRequestToMetadata::class);

        $result = $instance->process(
            ['empty' => true],
            $this->objectManager->create(OrderInterface::class),
            $this->objectManager->create(OrderItemInterface::class),
        );

        $this->assertEquals(['empty' => true], $result);
    }

    public function testDoesNothingWhenThereIsMetadataAvailable(): void
    {
        /** @var BuyRequestToMetadata $instance */
        $instance = $this->objectManager->create(BuyRequestToMetadata::class);

        /** @var OrderItemInterface $orderItem */
        $orderItem = $this->objectManager->create(OrderItemInterface::class);
        $orderItem->setProductOptions(['empty' => 'yep, also empty']);

        $result = $instance->process(
            ['empty' => true],
            $this->objectManager->create(OrderInterface::class),
            $orderItem,
        );

        $this->assertEquals(['empty' => true], $result);
    }

    public function testIncludesTheMetadataWhenItsPresent(): void
    {
        /** @var BuyRequestToMetadata $instance */
        $instance = $this->objectManager->create(BuyRequestToMetadata::class);

        /** @var OrderItemInterface $orderItem */
        $orderItem = $this->objectManager->create(OrderItemInterface::class);
        $orderItem->setProductOptions([
            'empty' => false,
            'info_buyRequest' => [
                'mollie_metadata' => ['mollie_subscriptions_product' => 1],
            ],
        ]);

        $result = $instance->process(
            ['empty' => false],
            $this->objectManager->create(OrderInterface::class),
            $orderItem,
        );

        $this->assertEquals(['empty' => false, 'metadata' => ['mollie_subscriptions_product' => 1]], $result);
    }
}
