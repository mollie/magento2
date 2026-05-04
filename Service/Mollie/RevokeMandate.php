<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Service\Mollie;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\Session;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;

class RevokeMandate
{
    public function __construct(
        private Session $customerSession,
        private CustomerRepositoryInterface $customerRepository,
        private MollieApiClient $mollieApiClient,
    ) {}

    public function execute(string $mandateId, ?int $storeId = null): void
    {
        if (!$this->customerSession->isLoggedIn()) {
            throw new LocalizedException(__('You must be logged in to revoke a saved card.'));
        }

        try {
            $customer = $this->customerRepository->getById((int)$this->customerSession->getCustomerId());
        } catch (NoSuchEntityException $e) {
            throw new LocalizedException(__('Customer not found.'), $e);
        }

        $mollieCustomerId = $customer->getExtensionAttributes()?->getMollieCustomerId();
        if (!$mollieCustomerId) {
            throw new LocalizedException(__('No saved cards found.'));
        }

        $api = $this->mollieApiClient->loadByStore($storeId);
        // revokeForId throws if the mandate does not belong to this customer, providing authorization.
        $api->mandates->revokeForId($mollieCustomerId, $mandateId);
    }
}
