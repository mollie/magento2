<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Integration\Service\Order\Lines;

use Magento\Sales\Api\Data\CreditmemoInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Mollie\Payment\Exceptions\NoStoreCreditFound;
use Mollie\Payment\Service\Order\Lines\StoreCredit;
use Mollie\Payment\Test\Integration\IntegrationTestCase;

class StoreCreditTest extends IntegrationTestCase
{
    public function orderHasStoreCreditProvider(): array
    {
        return [
            ['amstorecredit_amount'],
            ['customer_balance_amount'],
        ];
    }

    /**
     * @dataProvider orderHasStoreCreditProvider
     */
    public function testOrderHasStoreCreditReturnsTrueWhenApplicable(string $column): void
    {
        /** @var OrderInterface $order */
        $order = $this->objectManager->create(OrderInterface::class);
        $order->setData($column, 20);

        /** @var StoreCredit $instance */
        $instance = $this->objectManager->get(StoreCredit::class);

        $this->assertTrue(
            $instance->orderHasStoreCredit($order),
            'The order has a store credit but the method can\'t find it',
        );
    }

    /**
     * @dataProvider orderHasStoreCreditProvider
     */
    public function testCreditmemoHasStoreCredit(string $column): void
    {
        /** @var CreditmemoInterface $creditmemo */
        $creditmemo = $this->objectManager->create(CreditmemoInterface::class);
        $creditmemo->setData($column, 20);

        /** @var StoreCredit $instance */
        $instance = $this->objectManager->get(StoreCredit::class);

        $this->assertTrue(
            $instance->creditmemoHasStoreCredit($creditmemo),
            'The creditmemo has a store credit but the method can\'t find it',
        );
    }

    public function testOrderHasStoreCreditReturnsFalseWhenNotApplicable(): void
    {
        /** @var OrderInterface $order */
        $order = $this->objectManager->create(OrderInterface::class);

        /** @var StoreCredit $instance */
        $instance = $this->objectManager->get(StoreCredit::class);

        $this->assertFalse(
            $instance->orderHasStoreCredit($order),
            'The order doesn\'t have a store credit but the method thinks it does.',
        );
    }

    public function testCreditmemoHasStoreCreditReturnsFalseWhenNotApplicable(): void
    {
        /** @var CreditmemoInterface $creditmemo */
        $creditmemo = $this->objectManager->create(CreditmemoInterface::class);

        /** @var StoreCredit $instance */
        $instance = $this->objectManager->get(StoreCredit::class);

        $this->assertFalse(
            $instance->creditmemoHasStoreCredit($creditmemo),
            'The creditmemo doesn\'t have a store credit but the method thinks it does.',
        );
    }

    public function testThrowsAnExceptionWhenTheStoreCreditCantBeFound(): void
    {
        /** @var OrderInterface $order */
        $order = $this->objectManager->create(OrderInterface::class);
        $order->setEntityId(999);

        /** @var StoreCredit $instance */
        $instance = $this->objectManager->get(StoreCredit::class);

        try {
            $instance->getOrderLine($order, true);
        } catch (NoStoreCreditFound $exception) {
            $this->assertEquals(
                'We were unable to find the store credit for order #999',
                $exception->getMessage(),
            );

            return;
        }

        $this->fail('We expected a ' . NoStoreCreditFound::class . ' exception but got none');
    }

    public function createsTheOrderLineProvider(): array
    {
        return [
            [['amstorecredit_amount', 'amstorecredit_base_amount']],
            [['customer_balance_amount', 'base_customer_balance_amount']],
        ];
    }

    /**
     * @dataProvider createsTheOrderLineProvider
     */
    public function testCreatesTheOrderLine(array $fields): void
    {
        /** @var OrderInterface $order */
        $order = $this->objectManager->create(OrderInterface::class);
        $order->setBaseCurrencyCode('EUR');
        foreach ($fields as $field) {
            $order->setData($field, 20);
        }

        /** @var StoreCredit $instance */
        $instance = $this->objectManager->get(StoreCredit::class);
        $result = $instance->getOrderLine($order, true);

        $this->assertEquals('store_credit', $result['type']);
        $this->assertEquals(__('Store Credit'), $result['name']);
        $this->assertEquals(1, $result['quantity']);
        $this->assertEquals(['currency' => 'EUR', 'value' => '-20.00'], $result['unitPrice']);
        $this->assertEquals(['currency' => 'EUR', 'value' => '-20.00'], $result['totalAmount']);
        $this->assertEquals('0.00', $result['vatRate']);
        $this->assertEquals(['currency' => 'EUR', 'value' => '0.00'], $result['vatAmount']);
    }
}
