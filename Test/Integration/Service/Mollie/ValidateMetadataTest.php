<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Integration\Service\Mollie;

use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\OrderInterface;
use Mollie\Payment\Service\Mollie\ValidateMetadata;
use Mollie\Payment\Test\Integration\IntegrationTestCase;
use stdClass;

class ValidateMetadataTest extends IntegrationTestCase
{
    public function testThrowsExceptionWhenTheOrderIdIsNotCorrect(): void
    {
        $metadata = new stdClass();
        $metadata->order_id = 1;

        /** @var ValidateMetadata $instance */
        $instance = $this->objectManager->create(ValidateMetadata::class);

        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('Order ID does not match');

        $instance->execute($metadata, $this->getOrder());
    }

    public function testThrowsExceptionWhenTheOrderIsNotInOrderIds(): void
    {
        $metadata = new stdClass();
        $metadata->order_ids = '1, 2, 3';

        /** @var ValidateMetadata $instance */
        $instance = $this->objectManager->create(ValidateMetadata::class);

        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('Order ID does not match');

        $instance->execute($metadata, $this->getOrder());
    }

    public function testThrowsExceptionWhenNoMetadataIsSet(): void
    {
        $metadata = new stdClass();
        /** @var ValidateMetadata $instance */
        $instance = $this->objectManager->create(ValidateMetadata::class);

        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('No metadata found for order 999');

        $instance->execute($metadata, $this->getOrder());
    }

    public function testValidationCanBeSkipped(): void
    {
        /** @var ValidateMetadata $instance */
        $instance = $this->objectManager->create(ValidateMetadata::class);

        $instance->skipValidation();

        $instance->execute(null, $this->getOrder());

        $this->expectNotToPerformAssertions();
    }

    public function getOrder(): OrderInterface
    {
        /** @var OrderInterface $order */
        $order = $this->objectManager->get(OrderInterface::class);
        $order->setEntityId(999);

        return $order;
    }
}
