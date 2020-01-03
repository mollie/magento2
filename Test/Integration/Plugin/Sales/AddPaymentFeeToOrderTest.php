<?php
/**
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Plugin\Sales;

use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderSearchResultInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Mollie\Payment\Test\Integration\IntegrationTestCase;

class AddPaymentFeeToOrderTest extends IntegrationTestCase
{
    public function testAddsTheFeeToTheSearchResults()
    {
        /** @var AddPaymentFeeToOrder $instance */
        $instance = $this->objectManager->create(AddPaymentFeeToOrder::class);

        $repository = $this->objectManager->create(OrderRepositoryInterface::class);
        $searchResults = $this->objectManager->create(OrderSearchResultInterface::class);

        /** @var OrderInterface $entity */
        $entity = $this->objectManager->create(OrderInterface::class);
        $entity->setData('mollie_payment_fee', 1.6116);
        $entity->setData('mollie_payment_fee_tax', 0.3384);

        $searchResults->setItems([$entity]);

        $instance->afterGetList($repository, $searchResults);

        $extensionAttributes = $entity->getExtensionAttributes();
        $this->assertEquals(1.6116, $extensionAttributes->getMolliePaymentFee());
        $this->assertEquals(0.3384, $extensionAttributes->getMolliePaymentFeeTax());
    }

    public function testAddsTheFeeToASingleItem()
    {
        /** @var AddPaymentFeeToOrder $instance */
        $instance = $this->objectManager->create(AddPaymentFeeToOrder::class);

        $repository = $this->objectManager->create(OrderRepositoryInterface::class);

        /** @var OrderInterface $entity */
        $entity = $this->objectManager->create(OrderInterface::class);
        $entity->setData('mollie_payment_fee', 1.6116);
        $entity->setData('mollie_payment_fee_tax', 0.3384);

        $instance->afterGet($repository, $entity);

        $extensionAttributes = $entity->getExtensionAttributes();
        $this->assertEquals(1.6116, $extensionAttributes->getMolliePaymentFee());
        $this->assertEquals(0.3384, $extensionAttributes->getMolliePaymentFeeTax());
    }

    public function testSetsTheValueToNullWhenNoPaymentFeeIsPresent()
    {
        /** @var AddPaymentFeeToOrder $instance */
        $instance = $this->objectManager->create(AddPaymentFeeToOrder::class);

        $repository = $this->objectManager->create(OrderRepositoryInterface::class);

        /** @var OrderInterface $entity */
        $entity = $this->objectManager->create(OrderInterface::class);

        $instance->afterGet($repository, $entity);

        $extensionAttributes = $entity->getExtensionAttributes();
        $this->assertNull($extensionAttributes->getMolliePaymentFee());
        $this->assertNull($extensionAttributes->getMolliePaymentFeeTax());
    }
}
