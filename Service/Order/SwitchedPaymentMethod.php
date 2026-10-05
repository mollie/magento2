<?php

/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Service\Order;

class SwitchedPaymentMethod
{
    private const METHOD_PREFIX = 'mollie_methods_';

    private const METHODS_WITH_OWN_BREAKDOWN = ['giftcard', 'voucher'];

    private const EQUIVALENT_MOLLIE_METHODS = [
        'googlepay' => 'creditcard',
        'klarnapaylater' => 'klarna',
        'klarnapaynow' => 'klarna',
        'klarnasliceit' => 'klarna',
    ];

    public function execute(string $selectedMethodCode, ?string $mollieMethod): ?string
    {
        if ($mollieMethod === null || $mollieMethod === '') {
            return null;
        }

        $selectedMethod = str_replace(self::METHOD_PREFIX, '', $selectedMethodCode);
        if (in_array($selectedMethod, self::METHODS_WITH_OWN_BREAKDOWN, true)) {
            return null;
        }

        if ($this->isSameMethod($selectedMethod, $mollieMethod)) {
            return null;
        }

        return $mollieMethod;
    }

    private function isSameMethod(string $selectedMethod, string $mollieMethod): bool
    {
        return $selectedMethod === $mollieMethod
            || (self::EQUIVALENT_MOLLIE_METHODS[$selectedMethod] ?? null) === $mollieMethod;
    }
}
