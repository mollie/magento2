<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Plugin\Customer;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\Api\SearchResults;
use Mollie\Payment\Api\Data\MollieCustomerInterface;
use Mollie\Payment\Api\Data\MollieCustomerInterfaceFactory;
use Mollie\Payment\Api\MollieCustomerRepositoryInterface;

class CustomerId
{
    public function __construct(
        private MollieCustomerRepositoryInterface $repository,
        private MollieCustomerInterfaceFactory $mollieCustomerFactory
    ) {}

    public function aroundSave(
        CustomerRepositoryInterface $subject,
        callable $proceed,
        CustomerInterface $customer,
        $passwordHash = null,
    ) {
        $extensionAttributes = $customer->getExtensionAttributes();
        if (!$extensionAttributes) {
            return $proceed($customer, $passwordHash);
        }

        $mollieId = $extensionAttributes->getMollieCustomerId();
        $result = $proceed($customer, $passwordHash);

        if (!$mollieId) {
            return $result;
        }

        $mollieCustomer = $this->getCustomerModel($customer);
        $mollieCustomer->setMollieCustomerId($mollieId);

        $this->repository->save($mollieCustomer);

        return $result;
    }

    public function afterGet(CustomerRepositoryInterface $subject, CustomerInterface $customer): CustomerInterface
    {
        $this->retrieveForCustomer($customer);

        return $customer;
    }

    public function afterGetById(CustomerRepositoryInterface $subject, CustomerInterface $customer): CustomerInterface
    {
        $this->retrieveForCustomer($customer);

        return $customer;
    }

    public function afterGetList(CustomerRepositoryInterface $subject, SearchResults $result): SearchResults
    {
        /** @var CustomerInterface $customer */
        foreach ($result->getItems() as $customer) {
            $this->retrieveForCustomer($customer);
        }

        return $result;
    }

    /**
     * @param CustomerInterface $customer
     * @return MollieCustomerInterface
     */
    private function getCustomerModel(CustomerInterface $customer)
    {
        if ($mollieCustomer = $this->repository->getByCustomer($customer)) {
            return $mollieCustomer;
        }

        /** @var MollieCustomerInterface $mollieCustomer */
        $mollieCustomer = $this->mollieCustomerFactory->create();
        $mollieCustomer->setCustomerId($customer->getId());

        return $mollieCustomer;
    }

    private function retrieveForCustomer(CustomerInterface $customer): void
    {
        $extensionAttributes = $customer->getExtensionAttributes();
        if (!$extensionAttributes) {
            return;
        }

        $model = $this->repository->getByCustomer($customer);

        if ($model) {
            $extensionAttributes->setMollieCustomerId($model->getMollieCustomerId());
        }
    }
}
