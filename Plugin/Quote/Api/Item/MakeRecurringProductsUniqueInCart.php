<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Plugin\Quote\Api\Item;

use Magento\Framework\Serialize\SerializerInterface;
use Magento\Quote\Api\Data\CartItemInterface;

class MakeRecurringProductsUniqueInCart
{
    public function __construct(
        private SerializerInterface $serializer
    ) {}

    /**
     * @param CartItemInterface $item
     * @param bool $result
     * @return bool
     */
    public function afterRepresentProduct(CartItemInterface $item, bool $result): bool
    {
        $buyRequest = $item->getOptionByCode('info_buyRequest');
        if (!$buyRequest) {
            return $result;
        }

        if (
            (
                strstr($buyRequest->getValue(), 'is_recurring') !== false &&
                $this->jsonContainsRecurringValue($buyRequest->getValue())
            ) ||
            strstr($buyRequest->getValue(), 'purchase') !== false
        ) {
            return false;
        }

        return $result;
    }

    private function jsonContainsRecurringValue(string $json): bool
    {
        $data = $this->serializer->unserialize($json);

        return isset($data['mollie_metadata'], $data['mollie_metadata']['is_recurring']) &&
            $data['mollie_metadata']['is_recurring'] == 1;
    }
}
