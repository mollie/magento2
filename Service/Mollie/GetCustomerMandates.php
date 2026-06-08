<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Service\Mollie;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;

class GetCustomerMandates
{
    public function __construct(
        private CustomerRepositoryInterface $customerRepository,
        private MollieApiClient $mollieApiClient,
    ) {}

    public function execute(int $customerId, ?int $storeId = null): array
    {
        try {
            $customer = $this->customerRepository->getById($customerId);
        } catch (NoSuchEntityException) {
            return [];
        }

        $mollieCustomerId = $customer->getExtensionAttributes()?->getMollieCustomerId();
        if (!$mollieCustomerId) {
            return [];
        }

        $api = $this->mollieApiClient->loadByStore($storeId);
        $mandates = [];

        foreach ($api->mandates->iteratorForId($mollieCustomerId) as $mandate) {
            if ($mandate->method !== 'creditcard' || !$mandate->isValid()) {
                continue;
            }

            $details = $mandate->details;
            $mandates[] = [
                'mandate_id' => $mandate->id,
                'card_label' => $details->cardLabel ?? '',
                'card_number_last4' => $details->cardNumber ?? '',
                'card_expiry_date' => $details->cardExpiryDate ?? null,
                'card_holder' => $details->cardHolder ?? null,
            ];
        }

        return $mandates;
    }
}
