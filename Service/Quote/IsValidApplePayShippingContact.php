<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Service\Quote;

use Magento\Directory\Helper\Data as DirectoryHelper;
use Magento\Directory\Model\AllowedCountries;
use Magento\Store\Model\ScopeInterface;

class IsValidApplePayShippingContact
{
    public function __construct(
        private AllowedCountries $allowedCountries,
        private DirectoryHelper $directoryHelper,
    ) {}

    public function check(string $countryCode, string $postalCode, int $storeId): bool
    {
        return $this->isAllowedCountry($countryCode, $storeId) &&
            $this->hasRequiredPostalCode($countryCode, $postalCode);
    }

    private function isAllowedCountry(string $countryCode, int $storeId): bool
    {
        return in_array(
            $countryCode,
            $this->allowedCountries->getAllowedCountries(ScopeInterface::SCOPE_STORE, $storeId),
            true,
        );
    }

    private function hasRequiredPostalCode(string $countryCode, string $postalCode): bool
    {
        return $postalCode !== '' || $this->directoryHelper->isZipCodeOptional($countryCode);
    }
}
