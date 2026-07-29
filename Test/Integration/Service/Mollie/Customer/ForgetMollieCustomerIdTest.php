<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Integration\Service\Mollie\Customer;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Sales\Api\Data\OrderInterface;
use Mollie\Payment\Api\Data\MollieCustomerInterface;
use Mollie\Payment\Service\Mollie\Customer\ForgetMollieCustomerId;
use Mollie\Payment\Test\Integration\IntegrationTestCase;

class ForgetMollieCustomerIdTest extends IntegrationTestCase
{
    /**
     * Deliberately different from the customer ID, as the row has to be removed by its own primary key.
     */
    private const ENTITY_ID = 4200;

    /**
     * @magentoDataFixture Magento/Sales/_files/order_with_customer.php
     */
    public function testRemovesTheStoredMollieCustomer(): void
    {
        $order = $this->loadOrder('100000001');
        $this->storeMollieCustomerId((int)$order->getCustomerId(), 'cst_no_longer_available');

        /** @var ForgetMollieCustomerId $instance */
        $instance = $this->objectManager->create(ForgetMollieCustomerId::class);

        $this->assertTrue($instance->execute($order));
        $this->assertEquals(0, $this->countStoredMollieCustomers((int)$order->getCustomerId()));
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order_with_customer.php
     */
    public function testStopsExposingTheMollieCustomerIdOnTheCustomer(): void
    {
        $order = $this->loadOrder('100000001');
        $this->storeMollieCustomerId((int)$order->getCustomerId(), 'cst_no_longer_available');

        /** @var ForgetMollieCustomerId $instance */
        $instance = $this->objectManager->create(ForgetMollieCustomerId::class);

        $this->assertEquals('cst_no_longer_available', $this->getMollieCustomerIdAttribute($order));

        $instance->execute($order);

        $this->assertNull($this->getMollieCustomerIdAttribute($order));
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order_with_customer.php
     */
    public function testReturnsFalseWhenNothingIsStored(): void
    {
        $order = $this->loadOrder('100000001');

        /** @var ForgetMollieCustomerId $instance */
        $instance = $this->objectManager->create(ForgetMollieCustomerId::class);

        $this->assertFalse($instance->execute($order));
    }

    public function testReturnsFalseWhenTheOrderHasNoCustomer(): void
    {
        /** @var OrderInterface $order */
        $order = $this->objectManager->create(OrderInterface::class);

        /** @var ForgetMollieCustomerId $instance */
        $instance = $this->objectManager->create(ForgetMollieCustomerId::class);

        $this->assertFalse($instance->execute($order));
    }

    private function storeMollieCustomerId(int $customerId, string $mollieCustomerId): void
    {
        /** @var ResourceConnection $resource */
        $resource = $this->objectManager->get(ResourceConnection::class);

        $resource->getConnection()->insert($resource->getTableName('mollie_payment_customer'), [
            MollieCustomerInterface::ENTITY_ID => static::ENTITY_ID,
            MollieCustomerInterface::CUSTOMER_ID => $customerId,
            MollieCustomerInterface::MOLLIE_CUSTOMER_ID => $mollieCustomerId,
        ]);
    }

    private function getMollieCustomerIdAttribute(OrderInterface $order): ?string
    {
        $customer = $this->objectManager->create(CustomerRepositoryInterface::class)
            ->getById((int)$order->getCustomerId());

        return $customer->getExtensionAttributes()->getMollieCustomerId();
    }

    private function countStoredMollieCustomers(int $customerId): int
    {
        /** @var ResourceConnection $resource */
        $resource = $this->objectManager->get(ResourceConnection::class);
        $connection = $resource->getConnection();

        $select = $connection->select()
            ->from($resource->getTableName('mollie_payment_customer'), 'COUNT(*)')
            ->where(MollieCustomerInterface::CUSTOMER_ID . ' = ?', $customerId);

        return (int)$connection->fetchOne($select);
    }
}
