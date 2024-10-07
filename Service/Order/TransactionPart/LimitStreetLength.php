<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Service\Order\TransactionPart;

use Magento\Sales\Api\Data\OrderInterface;
use Mollie\Payment\Model\Client\Orders;
use Mollie\Payment\Model\Client\Payments;
use Mollie\Payment\Service\Order\TransactionPartInterface;

class LimitStreetLength implements TransactionPartInterface
{
    private $streetTruncated = false;

    public function process(OrderInterface $order, $apiMethod, array $transaction): array
    {
        if ($apiMethod == Orders::CHECKOUT_TYPE) {
            $transaction['billingAddress']['streetAndNumber'] = $this->limitStreetLength($transaction['billingAddress']['streetAndNumber']);
            $transaction['shippingAddress']['streetAndNumber'] = $this->limitStreetLength($transaction['shippingAddress']['streetAndNumber']);
        }

        if ($apiMethod == Payments::CHECKOUT_TYPE) {
            $transaction['billingAddress']['streetAndNumber'] = $this->limitStreetLength($transaction['billingAddress']['streetAndNumber']);
        }

        if ($apiMethod == Payments::CHECKOUT_TYPE && array_key_exists('shippingAddress', $transaction)) {
            $transaction['shippingAddress']['streetAndNumber'] = $this->limitStreetLength($transaction['shippingAddress']['streetAndNumber']);
        }

        if ($this->streetTruncated) {
            $transaction['metadata']['street_truncated'] = true;
        }

        return $transaction;
    }

    private function limitStreetLength(string $street): string
    {
        if (mb_strlen($street) <= 100) {
            return $street;
        }

        $this->streetTruncated = true;
        return mb_substr($street, 0, 100);
    }
}
