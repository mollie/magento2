<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Service\Order\TransactionPart;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Mollie\Payment\Config;
use Mollie\Payment\Model\Api;
use Mollie\Payment\Service\Order\OrderContainsSubscriptionProduct;
use Mollie\Payment\Service\Order\TransactionPartInterface;

class CustomerId implements TransactionPartInterface
{
    public function __construct(
        private Api $api,
        private CustomerRepositoryInterface $customerRepository,
        private Config $config,
        private OrderContainsSubscriptionProduct $orderContainsSubscriptionProduct
    ) {}

    public function process(OrderInterface $order, array $transaction): array
    {
        if (!$this->shouldCreateCustomerId($order) || !$order->getCustomerId()) {
            return $transaction;
        }

        $transaction['customerId'] = $this->getCustomerId($order);

        return $transaction;
    }

    private function getCustomerId(OrderInterface $order): string
    {
        $customer = $this->customerRepository->getById($order->getCustomerId());
        $attribute = $customer->getExtensionAttributes()->getMollieCustomerId();

        if ($attribute) {
            return $attribute;
        }

        return $this->getCustomerIdFromMollie($order);
    }

    private function getCustomerIdFromMollie(OrderInterface $order): string
    {
        $this->api->load(storeId($order->getStoreId()));
        $mollieCustomer = $this->api->customers->create([
            'name' => $order->getCustomerName(),
            'email' => $order->getCustomerEmail(),
        ]);

        $customer = $this->customerRepository->getById($order->getCustomerId());
        if ($customer->getExtensionAttributes()) {
            $customer->getExtensionAttributes()->setMollieCustomerId($mollieCustomer->id);
            $this->customerRepository->save($customer);
        }

        return $mollieCustomer->id;
    }

    private function shouldCreateCustomerId(OrderInterface $order): bool
    {
        if ($this->orderContainsSubscriptionProduct->check($order)) {
            return true;
        }

        $storeId = storeId($order->getStoreId());
        if ($this->config->isMagentoVaultEnabled($storeId)) {
            return true;
        }

        if ($order->getPayment()->getMethod() != 'mollie_methods_creditcard') {
            return false;
        }

        if (!$this->config->creditcardEnableCustomersApi($storeId)) {
            return false;
        }

        return true;
    }
}
