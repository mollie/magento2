<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Plugin\Quote\Api;

use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\PaymentMethodManagementInterface;
use Mollie\Payment\Service\Quote\CartContainsRecurringProduct;

class LimitMethodsForRecurringPayments
{
    public const ALLOWED_METHODS = [
        'mollie_methods_bancontact',
        'mollie_methods_belfius',
        'mollie_methods_creditcard',
        'mollie_methods_eps',
        'mollie_methods_ideal',
        'mollie_methods_kbc',
        'mollie_methods_mybank',
        'mollie_methods_paybybank',
        'mollie_methods_paypal',
        'mollie_methods_satispay',
        'mollie_methods_trustly',
        'mollie_methods_sofort',
        'mollie_methods_twint',
    ];

    public function __construct(
        private CartRepositoryInterface $cartRepository,
        private CartContainsRecurringProduct $cartContainsRecurringProduct
    ) {}

    public function afterGetList(PaymentMethodManagementInterface $subject, $result, $cartId): array
    {
        $cart = $this->cartRepository->get($cartId);

        if (!$this->cartContainsRecurringProduct->execute($cart)) {
            return $result;
        }

        return array_filter($result, function ($method): bool {
            return in_array($method->getCode(), static::ALLOWED_METHODS);
        });
    }
}
