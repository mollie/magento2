<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Service\Mollie\Customer;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\Data\OrderInterface;
use Mollie\Payment\Api\MollieCustomerRepositoryInterface;

class ForgetMollieCustomerId
{
    public function __construct(
        private CustomerRepositoryInterface $customerRepository,
        private MollieCustomerRepositoryInterface $mollieCustomerRepository,
    ) {}

    public function execute(OrderInterface $order): bool
    {
        $customerId = $order->getCustomerId();
        if ($customerId === null) {
            return false;
        }

        try {
            $customer = $this->customerRepository->getById((int)$customerId);
        } catch (NoSuchEntityException) {
            return false;
        }

        $mollieCustomer = $this->mollieCustomerRepository->getByCustomer($customer);
        if ($mollieCustomer === null) {
            return false;
        }

        $this->mollieCustomerRepository->delete($mollieCustomer);

        return true;
    }
}
