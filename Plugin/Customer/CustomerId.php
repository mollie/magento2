<?php
/**
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Plugin\Customer;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Mollie\Payment\Api\Data\MollieCustomerInterface;
use Mollie\Payment\Api\Data\MollieCustomerInterfaceFactory;
use Mollie\Payment\Api\MollieCustomerRepositoryInterface;

class CustomerId
{
    /**
     * @var MollieCustomerRepositoryInterface
     */
    private $repository;

    /**
     * @var MollieCustomerInterfaceFactory
     */
    private $mollieCustomerFactory;

    public function __construct(
        MollieCustomerRepositoryInterface $repository,
        MollieCustomerInterfaceFactory $mollieCustomerFactory
    ) {
        $this->repository = $repository;
        $this->mollieCustomerFactory = $mollieCustomerFactory;
    }

    public function aroundSave(
        CustomerRepositoryInterface $subject,
        callable $proceed,
        CustomerInterface $customer,
        $passwordHash = null
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

    public function afterGet(CustomerRepositoryInterface $subject, CustomerInterface $customer)
    {
        $this->retrieveForCustomer($customer);

        return $customer;
    }

    public function afterGetById(CustomerRepositoryInterface $subject, CustomerInterface $customer)
    {
        $this->retrieveForCustomer($customer);

        return $customer;
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

    private function retrieveForCustomer(CustomerInterface $customer)
    {
        $extensionAttributes = $customer->getExtensionAttributes();
        if (!$extensionAttributes) {
            return $customer;
        }

        $model = $this->repository->getByCustomer($customer);

        if ($model) {
            $extensionAttributes->setMollieCustomerId($model->getMollieCustomerId());
        }
    }
}
