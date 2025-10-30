<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Service\Mollie;

use Magento\Customer\Model\Session;
use Magento\Quote\Api\Data\CartInterface;
use Mollie\Payment\Config;

class PointOfSaleAvailability
{
    public function __construct(
        private Config $config,
        private Session $customerSession
    ) {}

    public function isAvailable(CartInterface $cart): bool
    {
        $storeId = storeId($cart->getStoreId());

        return $this->isAvailableForCustomerGroupId((int) $this->customerSession->getCustomerGroupId(), $storeId);
    }

    public function isAvailableForCustomerGroupId(int $customerGroupId, int $storeId): bool
    {
        $allowedGroups = explode(',', $this->config->pointofsaleAllowedCustomerGroups($storeId));

        return in_array(
            (string) $customerGroupId,
            $allowedGroups,
        );
    }
}
