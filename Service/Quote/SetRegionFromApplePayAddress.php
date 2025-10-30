<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Service\Quote;

use Magento\Directory\Api\CountryInformationAcquirerInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\Data\AddressInterface;

class SetRegionFromApplePayAddress
{
    public function __construct(
        private CountryInformationAcquirerInterface $countryInformationAcquirer
    ) {}

    public function execute(AddressInterface $address, array $input): void
    {
        if (!array_key_exists('administrativeArea', $input)) {
            return;
        }

        try {
            $information = $this->countryInformationAcquirer->getCountryInfo($input['countryCode']);
        } catch (NoSuchEntityException $exception) {
            return;
        }

        $regions = $information->getAvailableRegions();
        if ($regions === null) {
            $address->setRegion($input['administrativeArea']);

            return;
        }

        foreach ($regions as $region) {
            if ($region->getCode() === $input['administrativeArea']) {
                $address->setRegionId($region->getId());

                return;
            }
        }
    }
}
