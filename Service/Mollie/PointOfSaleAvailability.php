<?php

declare(strict_types=1);

namespace Mollie\Payment\Service\Mollie;

use Magento\Customer\Model\Session;
use Magento\Quote\Api\Data\CartInterface;
use Mollie\Payment\Config;

class PointOfSaleAvailability
{
    /**
     * @var Config
     */
    private $config;
    /**
     * @var Session
     */
    private $customerSession;

    public function __construct(
        Config $config,
        Session $customerSession
    ) {
        $this->config = $config;
        $this->customerSession = $customerSession;
    }

    public function isAvailable(CartInterface $cart): bool
    {
        $storeId = (int)$cart->getStoreId();

        return $this->isAvailableForCustomerGroupId((int)$this->customerSession->getCustomerGroupId(), $storeId);
    }

    public function isAvailableForCustomerGroupId(int $customerGroupId, int $storeId): bool
    {
        $allowedGroups = explode(',', $this->config->pointofsaleAllowedCustomerGroups($storeId));

        return in_array(
            (string)$customerGroupId,
            $allowedGroups
        );
    }
}
