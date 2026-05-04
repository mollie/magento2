<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Webapi;

use Magento\Authorization\Model\UserContextInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\StoreManagerInterface;
use Mollie\Payment\Api\Data\SavedCardInterface;
use Mollie\Payment\Api\Webapi\SavedCardsInterface;
use Mollie\Payment\Config;
use Mollie\Payment\Model\SavedCardFactory;
use Mollie\Payment\Service\Mollie\GetCustomerMandates;
use Mollie\Payment\Service\Mollie\RevokeMandate;

class SavedCards implements SavedCardsInterface
{
    public function __construct(
        private GetCustomerMandates $getCustomerMandates,
        private RevokeMandate $revokeMandate,
        private Config $config,
        private StoreManagerInterface $storeManager,
        private UserContextInterface $userContext,
        private SavedCardFactory $savedCardFactory,
    ) {}

    public function getList(): array
    {
        $storeId = storeId($this->storeManager->getStore()->getId());

        if (!$this->config->creditcardEnableCustomersApi($storeId)) {
            return [];
        }

        $customerId = (int)$this->userContext->getUserId();
        if (!$customerId) {
            return [];
        }

        return array_map(
            fn(array $item): SavedCardInterface => $this->savedCardFactory->create([
                'mandateId' => $item['mandate_id'],
                'cardLabel' => $item['card_label'],
                'cardNumberLast4' => $item['card_number_last4'],
                'cardExpiryDate' => $item['card_expiry_date'],
                'cardHolder' => $item['card_holder'],
            ]),
            $this->getCustomerMandates->execute($customerId, $storeId),
        );
    }

    public function delete(string $mandateId): bool
    {
        $storeId = storeId($this->storeManager->getStore()->getId());

        if (!$this->config->creditcardEnableCustomersApi($storeId)) {
            throw new LocalizedException(__('Saved cards are not enabled.'));
        }

        $this->revokeMandate->execute($mandateId, $storeId);

        return true;
    }
}
