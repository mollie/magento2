<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Service\Order\TransactionPart;

use InvalidArgumentException;
use Magento\Sales\Api\Data\OrderAddressInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Mollie\Payment\Service\Format\E164PhoneNumber;
use Mollie\Payment\Service\Order\TransactionPartInterface;

class PhoneNumber implements TransactionPartInterface
{
    public function __construct(
        private readonly E164PhoneNumber $e164PhoneNumber,
    ) {
    }

    /**
     * @param array<string, mixed> $transaction
     * @return array<string, mixed>
     */
    public function process(OrderInterface $order, array $transaction): array
    {
        $billingAddress = $order->getBillingAddress();
        if ($billingAddress !== null) {
            $transaction['billingAddress'] = $this->formatForAddress($billingAddress, $transaction['billingAddress']);
        }

        if (array_key_exists('shippingAddress', $transaction) && $order->getShippingAddress()) {
            $transaction['shippingAddress'] = $this->formatForAddress(
                $order->getShippingAddress(),
                $transaction['shippingAddress'],
            );
        }

        return $transaction;
    }

    /**
     * @param array<string, mixed> $transactionAddress
     * @return array<string, mixed>
     */
    private function formatForAddress(OrderAddressInterface $address, array $transactionAddress): array
    {
        $countryCode = $address->getCountryId();
        $phoneNumber = $address->getTelephone();

        if (!$countryCode || !$phoneNumber) {
            return $transactionAddress;
        }

        try {
            $transactionAddress['phone'] = $this->e164PhoneNumber->format($countryCode, $phoneNumber);

            return $transactionAddress;
        } catch (InvalidArgumentException $exception) {
            // Silently ignore the exception
        }

        return $transactionAddress;
    }
}
