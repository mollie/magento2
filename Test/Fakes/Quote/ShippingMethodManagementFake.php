<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Fakes\Quote;

use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Api\Data\EstimateAddressInterface;
use Magento\Quote\Api\ShippingMethodManagementInterface;

class ShippingMethodManagementFake implements ShippingMethodManagementInterface
{
    private ?LocalizedException $getListException = null;

    public function givenGetListFailsWith(LocalizedException $exception): self
    {
        $this->getListException = $exception;

        return $this;
    }

    public function estimateByAddress($cartId, EstimateAddressInterface $address): array
    {
        return [];
    }

    public function estimateByAddressId($cartId, $addressId): array
    {
        return [];
    }

    public function getList($cartId): array
    {
        if ($this->getListException !== null) {
            throw $this->getListException;
        }

        return [];
    }
}
