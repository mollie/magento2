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
        $transaction = $this->limitAddress('billingAddress', $transaction);
        $transaction = $this->limitAddress('shippingAddress', $transaction);

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

    private function limitAddress(string $type, array $transaction): array
    {
        if (array_key_exists($type, $transaction)) {
            $limited = $this->limitStreetLength($transaction[$type]['streetAndNumber']);
            $transaction[$type]['streetAndNumber'] = $limited;
        }

        return $transaction;
    }
}
