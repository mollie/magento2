<?php

/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Service\Order\TransactionPart;

use Magento\Sales\Api\Data\OrderInterface;
use Mollie\Payment\Service\Order\TransactionPartInterface;

class HandleUnusablePostalCode implements TransactionPartInterface
{
    private const BILLING_ADDRESS = 'billingAddress';
    private const SHIPPING_ADDRESS = 'shippingAddress';
    private const POSTAL_CODE = 'postalCode';
    private const COUNTRY = 'country';
    private const POSTAL_ADDRESS_FIELDS = [
        'streetAndNumber',
        'streetAdditional',
        self::POSTAL_CODE,
        'city',
        'region',
        self::COUNTRY,
    ];
    private const HAS_LETTER_OR_DIGIT = '/[\p{L}\p{N}]/u';
    private const COUNTRIES_MOLLIE_ACCEPTS_WITHOUT_POSTAL_CODE = [
        'AE', 'AO', 'AW', 'BF', 'BI', 'BJ', 'BO', 'BS', 'BV', 'BW', 'BZ', 'CD', 'CF', 'CG', 'CI', 'CK',
        'CM', 'DJ', 'DM', 'ER', 'FJ', 'GA', 'GD', 'GH', 'GM', 'GN', 'GQ', 'GY', 'HK', 'JM', 'KI', 'KM',
        'KN', 'KP', 'LC', 'ML', 'MO', 'MR', 'MS', 'MW', 'NR', 'NU', 'PA', 'QA', 'RW', 'SB', 'SC', 'SL',
        'SO', 'SR', 'ST', 'SY', 'TF', 'TK', 'TL', 'TO', 'TT', 'TV', 'UG', 'VU', 'YE', 'ZM', 'ZW',
    ];

    /**
     * @param array<string, mixed> $transaction
     * @return array<string, mixed>
     */
    public function process(OrderInterface $order, array $transaction): array
    {
        return $this->withShippingAddressMollieAccepts($this->withBillingAddressMollieAccepts($transaction));
    }

    /**
     * @param array<string, mixed> $transaction
     * @return array<string, mixed>
     */
    private function withBillingAddressMollieAccepts(array $transaction): array
    {
        if (!$this->hasUnusablePostalCode($transaction, self::BILLING_ADDRESS)) {
            return $transaction;
        }

        $address = $transaction[self::BILLING_ADDRESS];
        $transaction[self::BILLING_ADDRESS] = $this->isAcceptedWithoutPostalCode($address)
            ? $this->withoutPostalCode($address)
            : array_diff_key($address, array_flip(self::POSTAL_ADDRESS_FIELDS));

        return $transaction;
    }

    /**
     * @param array<string, mixed> $transaction
     * @return array<string, mixed>
     */
    private function withShippingAddressMollieAccepts(array $transaction): array
    {
        if (!$this->hasUnusablePostalCode($transaction, self::SHIPPING_ADDRESS)) {
            return $transaction;
        }

        $address = $transaction[self::SHIPPING_ADDRESS];
        if (!$this->isAcceptedWithoutPostalCode($address)) {
            return array_diff_key($transaction, [self::SHIPPING_ADDRESS => true]);
        }

        $transaction[self::SHIPPING_ADDRESS] = $this->withoutPostalCode($address);

        return $transaction;
    }

    /**
     * @param array<string, mixed> $transaction
     */
    private function hasUnusablePostalCode(array $transaction, string $type): bool
    {
        if (!is_array($transaction[$type] ?? null)) {
            return false;
        }

        return preg_match(self::HAS_LETTER_OR_DIGIT, (string)($transaction[$type][self::POSTAL_CODE] ?? '')) !== 1;
    }

    /**
     * @param array<string, mixed> $address
     * @return array<string, mixed>
     */
    private function withoutPostalCode(array $address): array
    {
        return array_diff_key($address, [self::POSTAL_CODE => true]);
    }

    /**
     * @param array<string, mixed> $address
     */
    private function isAcceptedWithoutPostalCode(array $address): bool
    {
        return in_array(
            strtoupper((string)($address[self::COUNTRY] ?? '')),
            self::COUNTRIES_MOLLIE_ACCEPTS_WITHOUT_POSTAL_CODE,
            true,
        );
    }
}
