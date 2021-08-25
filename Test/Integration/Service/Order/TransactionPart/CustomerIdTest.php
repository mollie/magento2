<?php
/**
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Test\IntegrationService\Order\TransactionPart;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\Customer;
use Mollie\Payment\Model\Client\Orders;
use Mollie\Payment\Model\Client\Payments;
use Mollie\Payment\Service\Order\TransactionPart\CustomerId;
use Mollie\Payment\Test\Integration\IntegrationTestCase;

class CustomerIdTest extends IntegrationTestCase
{
    /**
     * @magentoConfigFixture default_store payment/mollie_methods_creditcard/enable_customers_api 1
     * @magentoDataFixture Magento/Sales/_files/order_with_customer.php
     */
    public function testUsesTheCustomerAttributeOnPaymentsApi()
    {
        $order = $this->loadOrder('100000001');
        /** @var CustomerRepositoryInterface $customerRepository */
        $customerRepository = $this->objectManager->create(CustomerRepositoryInterface::class);
        $customer = $customerRepository->getById($order->getCustomerId());
        $customer->getExtensionAttributes()->setMollieCustomerId('abc123');
        $customerRepository->save($customer);

        $order->getPayment()->setMethod('mollie_methods_creditcard');

        /** @var CustomerId $instance */
        $instance = $this->objectManager->create(CustomerId::class);

        $transaction = $instance->process($order, Payments::CHECKOUT_TYPE, []);

        $this->assertArrayHasKey('customerId', $transaction);
        $this->assertEquals('abc123', $transaction['customerId']);
    }

    /**
     * @magentoConfigFixture default_store payment/mollie_methods_creditcard/enable_customers_api 1
     * @magentoDataFixture Magento/Sales/_files/order_with_customer.php
     */
    public function testUsesTheCustomerAttributeOnOrdersApi()
    {
        $order = $this->loadOrder('100000001');
        /** @var CustomerRepositoryInterface $customerRepository */
        $customerRepository = $this->objectManager->create(CustomerRepositoryInterface::class);
        $customer = $customerRepository->getById($order->getCustomerId());
        $customer->getExtensionAttributes()->setMollieCustomerId('abc123');
        $customerRepository->save($customer);

        $order->getPayment()->setMethod('mollie_methods_creditcard');

        /** @var CustomerId $instance */
        $instance = $this->objectManager->create(CustomerId::class);

        $transaction = $instance->process($order, Orders::CHECKOUT_TYPE, []);

        $this->assertArrayHasKey('customerId', $transaction['payment']);
        $this->assertEquals('abc123', $transaction['payment']['customerId']);
    }

    /**
     * @magentoConfigFixture default_store payment/mollie_methods_creditcard/enable_customers_api 1
     * @magentoDataFixture Magento/Sales/_files/order_with_customer.php
     */
    public function testDoesNothingWhenNotCreditcard()
    {
        $order = $this->loadOrder('100000001');

        $order->getPayment()->setMethod('mollie_methods_ideal');

        /** @var CustomerId $instance */
        $instance = $this->objectManager->create(CustomerId::class);

        $transaction = $instance->process($order, Payments::CHECKOUT_TYPE, []);

        $this->assertArrayNotHasKey('customerId', $transaction);
    }

    /**
     * @magentoConfigFixture default_store payment/mollie_methods_creditcard/enable_customers_api 0
     * @magentoDataFixture Magento/Sales/_files/order_with_customer.php
     */
    public function testDoesNothingWhenNotEnabled()
    {
        $order = $this->loadOrder('100000001');

        $order->getPayment()->setMethod('mollie_methods_creditcard');

        /** @var CustomerId $instance */
        $instance = $this->objectManager->create(CustomerId::class);

        $transaction = $instance->process($order, Payments::CHECKOUT_TYPE, []);

        $this->assertArrayNotHasKey('customerId', $transaction);
    }

    /**
     * @magentoConfigFixture default_store payment/mollie_methods_creditcard/enable_customers_api 0
     * @magentoDataFixture Magento/Sales/_files/order_with_customer.php
     */
    public function testWhenTheOrderContainsARecurringProductItShouldAddTheCustomerId()
    {
        $order = $this->loadOrder('100000001');

        /** @var CustomerRepositoryInterface $customerRepository */
        $customerRepository = $this->objectManager->create(CustomerRepositoryInterface::class);
        $customer = $customerRepository->getById($order->getCustomerId());
        $customer->getExtensionAttributes()->setMollieCustomerId('abc123');
        $customerRepository->save($customer);

        $items = $order->getItems();
        $item = array_shift($items);

        $item->setProductOptions($item->getProductOptions() + [
            'info_buyRequest' => [
                'qty' => 1,
                'mollie_metadata' => ['is_recurring' => true],
            ]
        ]);

        /** @var CustomerId $instance */
        $instance = $this->objectManager->create(CustomerId::class);

        $transaction = $instance->process($order, Payments::CHECKOUT_TYPE, []);

        $this->assertArrayHasKey('customerId', $transaction);
        $this->assertEquals('abc123', $transaction['customerId']);
    }
}
