<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Service\Quote;

use Magento\Framework\Serialize\SerializerInterface;
use Magento\Quote\Api\Data\CartInterface;

class CartContainsRecurringProduct
{
    /**
     * @var SerializerInterface
     */
    private $serializer;

    public function __construct(
        SerializerInterface $serializer
    ) {
        $this->serializer = $serializer;
    }

    public function execute(CartInterface $cart): bool
    {
        $items = $cart->getItemsCollection()->getItems();
        foreach ($items as $item) {
            $buyRequest = $item->getOptionByCode('info_buyRequest');
            if ($buyRequest && strstr($buyRequest->getValue(), 'is_recurring') !== false &&
                $this->jsonContainsRecurringValue($buyRequest->getValue())) {
                return true;
            }
        }

        return false;
    }

    private function jsonContainsRecurringValue(string $json): bool
    {
        $data = $this->serializer->unserialize($json);

        return isset($data['mollie_metadata'], $data['mollie_metadata']['is_recurring']) &&
            $data['mollie_metadata']['is_recurring'] == 1;
    }
}
