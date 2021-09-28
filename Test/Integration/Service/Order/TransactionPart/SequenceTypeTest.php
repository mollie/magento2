<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Test\Integration\Service\Order\TransactionPart;

use Magento\Customer\Model\Session;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Vault\Model\Ui\VaultConfigProvider;
use Mollie\Payment\Model\Client\Payments;
use Mollie\Payment\Model\Client\Orders;
use Mollie\Payment\Test\Integration\IntegrationTestCase;
use Mollie\Payment\Service\Order\OrderContainsSubscriptionProduct;
use Mollie\Payment\Service\Order\TransactionPart\SequenceType;

class SequenceTypeTest extends IntegrationTestCase
{
    public function testDoesNothingWhenTheCartDoesNotContainARecurringProduct()
    {
        $orderContainsSubscriptionProductMock = $this->createMock(OrderContainsSubscriptionProduct::class);
        $orderContainsSubscriptionProductMock->method('check')->willReturn(false);

        /** @var SequenceType $instance */
        $instance = $this->objectManager->create(SequenceType::class, [
            'orderContainsSubscriptionProduct' => $orderContainsSubscriptionProductMock,
        ]);
        $result = $instance->process(
            $this->objectManager->create(OrderInterface::class),
            Payments::CHECKOUT_TYPE,
            ['empty' => true]
        );

        $this->assertEquals(['empty' => true], $result);
    }

    public function testIncludesTheSequenceTypeForThePaymentsApi()
    {
        $orderContainsSubscriptionProductMock = $this->createMock(OrderContainsSubscriptionProduct::class);
        $orderContainsSubscriptionProductMock->method('check')->willReturn(true);

        /** @var SequenceType $instance */
        $instance = $this->objectManager->create(SequenceType::class, [
            'orderContainsSubscriptionProduct' => $orderContainsSubscriptionProductMock,
        ]);
        $result = $instance->process(
            $this->objectManager->create(OrderInterface::class),
            Payments::CHECKOUT_TYPE,
            ['empty' => false]
        );

        $this->assertEquals(['empty' => false, 'sequenceType' => 'first'], $result);
    }

    public function testIncludesTheSequenceTypeForTheOrdersApi()
    {
        $orderContainsSubscriptionProductMock = $this->createMock(OrderContainsSubscriptionProduct::class);
        $orderContainsSubscriptionProductMock->method('check')->willReturn(true);

        /** @var SequenceType $instance */
        $instance = $this->objectManager->create(SequenceType::class, [
            'orderContainsSubscriptionProduct' => $orderContainsSubscriptionProductMock,
        ]);
        $result = $instance->process(
            $this->objectManager->create(OrderInterface::class),
            Orders::CHECKOUT_TYPE,
            ['empty' => false, 'payment' => []]
        );

        $this->assertEquals(['empty' => false, 'payment' => ['sequenceType' => 'first']], $result);
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     * @magentoConfigFixture default_store payment/mollie_methods_creditcard/enable_customers_api 0
     */
    public function testIncludesNothingWhenTheCustomersApiIsDisabled()
    {
        /** @var OrderInterface $order */
        $order = $this->loadOrderById('100000001');
        $order->getPayment()->setAdditionalInformation(VaultConfigProvider::IS_ACTIVE_CODE, true);

        /** @var SequenceType $instance */
        $instance = $this->objectManager->create(SequenceType::class);

        $result = $instance->process(
            $order,
            Orders::CHECKOUT_TYPE,
            ['empty' => false, 'payment' => []]
        );

        $this->assertEquals(['empty' => false, 'payment' => []], $result);
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     * @magentoDataFixture Magento/Customer/_files/customer.php
     * @magentoConfigFixture default_store payment/mollie_methods_creditcard/enable_customers_api 1
     */
    public function testIncludesTheSequenceTypeWhenVaultIsEnabled()
    {
        /** @var OrderInterface $order */
        $order = $this->loadOrderById('100000001');
        $order->getPayment()->setAdditionalInformation(VaultConfigProvider::IS_ACTIVE_CODE, true);
        $order->getPayment()->setMethod('mollie_methods_creditcard');

        /** @var \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository */
        $customerRepository = $this->objectManager->create(\Magento\Customer\Api\CustomerRepositoryInterface::class);
        $customer = $customerRepository->getById(1);

        $this->objectManager->get(Session::class)
            ->setCustomerId($customer->getId())
            ->setCustomerGroupId($customer->getGroupId());

        /** @var SequenceType $instance */
        $instance = $this->objectManager->create(SequenceType::class);
        $result = $instance->process(
            $order,
            Orders::CHECKOUT_TYPE,
            ['empty' => false, 'payment' => []]
        );

        $this->assertEquals(['empty' => false, 'payment' => ['sequenceType' => 'first']], $result);
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testDoesNothingWhenLoggedIn()
    {
        /** @var OrderInterface $order */
        $order = $this->loadOrderById('100000001');
        $order->getPayment()->setAdditionalInformation(VaultConfigProvider::IS_ACTIVE_CODE, true);

        $this->objectManager->get(Session::class)->setCustomerId(null);

        /** @var SequenceType $instance */
        $instance = $this->objectManager->create(SequenceType::class);
        $result = $instance->process(
            $order,
            Orders::CHECKOUT_TYPE,
            ['empty' => false, 'payment' => []]
        );

        $this->assertEquals(['empty' => false, 'payment' => []], $result);
    }
}
