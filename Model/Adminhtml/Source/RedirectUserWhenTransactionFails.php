<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Model\Adminhtml\Source;

use Magento\Framework\Data\OptionSourceInterface;

class RedirectUserWhenTransactionFails implements OptionSourceInterface
{
    public const REDIRECT_TO_CART = 'redirect_to_cart';
    public const REDIRECT_TO_CHECKOUT_SHIPPING = 'redirect_to_checkout_shipping';
    public const REDIRECT_TO_CHECKOUT_PAYMENT = 'redirect_to_checkout_payment';

    public function toOptionArray(): array
    {
        return [
            [
                'value' => static::REDIRECT_TO_CART,
                'label' => __('Redirect to cart'),
            ],
            [
                'value' => static::REDIRECT_TO_CHECKOUT_SHIPPING,
                'label' => __('Redirect to checkout (shipping)'),
            ],
            [
                'value' => static::REDIRECT_TO_CHECKOUT_PAYMENT,
                'label' => __('Redirect to checkout (payment)'),
            ],
        ];
    }
}
