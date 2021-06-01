<?php
/**
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Api;

use Magento\Customer\Api\Data\CustomerInterface;

interface MollieCustomerRepositoryInterface
{
    /**
     * Save Customer
     * @param \Mollie\Payment\Api\Data\MollieCustomerInterface $customer
     * @return \Mollie\Payment\Api\Data\MollieCustomerInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function save(
        \Mollie\Payment\Api\Data\MollieCustomerInterface $customer
    );

    /**
     * Retrieve Customer
     * @param string $id
     * @return \Mollie\Payment\Api\Data\MollieCustomerInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function get($id);

    /**
     * Retrieve Mollie Customer connection by Mollie Customer ID
     * @param string $customerId
     * @return \Mollie\Payment\Api\Data\MollieCustomerInterface
     */
    public function getByMollieCustomerId(string $customerId);

    /**
     * Retrieve Mollie Customer by Magento customer
     * @param \Magento\Customer\Api\Data\CustomerInterface $customer
     * @return \Mollie\Payment\Api\Data\MollieCustomerInterface
     */
    public function getByCustomer(CustomerInterface $customer);

    /**
     * Retrieve Customer matching the specified criteria.
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \Magento\Framework\Api\SearchResultsInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
    );

    /**
     * Delete Customer
     * @param \Mollie\Payment\Api\Data\MollieCustomerInterface $customer
     * @return bool true on success
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function delete(
        \Mollie\Payment\Api\Data\MollieCustomerInterface $customer
    );

    /**
     * Delete Customer by ID
     * @param string $customerId
     * @return bool true on success
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function deleteById($customerId);
}
