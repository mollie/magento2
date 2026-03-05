<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Service\Mollie\Order;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Api\Data\CustomerInterfaceFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Store\Model\StoreManagerInterface;
use Mollie\Api\Resources\Payment;

class GetCustomerFromPayment
{
    public function __construct(
        private readonly StoreManagerInterface $storeManager,
        private readonly CustomerRepositoryInterface $customerRepository,
        private readonly CustomerInterfaceFactory $customerFactory,
    ) {}

    public function execute(CartInterface $oldCart, Payment $payment): CustomerInterface
    {
        $billingAddress = $payment->billingAddress;

        try {
            $email = $this->getEmail($oldCart, $billingAddress);
            return $this->customerRepository->get($email);
        } catch (NoSuchEntityException) {
        }

        $store = $this->storeManager->getStore();

        $shippingAddress = $oldCart->getShippingAddress();

        $customer = $this->customerFactory->create();
        $customer
            ->setWebsiteId($store->getWebsiteId())
            ->setStoreId($store->getId())
            ->setFirstname($billingAddress->givenName ?? $shippingAddress->getFirstname())
            ->setLastname($billingAddress->familyName ?? $shippingAddress->getLastname())
            ->setEmail($email);

        return $this->customerRepository->save($customer);
    }

    private function getEmail(CartInterface $oldCart, ?\stdClass $billingAddress): string
    {
        if (is_object($billingAddress) && property_exists($billingAddress, 'email')) {
            return $billingAddress->email;
        }

        $email = $oldCart->getPayment()->getAdditionalInformation('mollie_guest_email');

        if (!$email) {
            throw new NoSuchEntityException(__('No email address found for this payment.'));
        }

        return $email;
    }
}
