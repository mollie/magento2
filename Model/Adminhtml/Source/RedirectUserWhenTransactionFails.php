<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Model\Adminhtml\Source;

use Magento\Framework\Data\OptionSourceInterface;

class RedirectUserWhenTransactionFails implements OptionSourceInterface
{
    const REDIRECT_TO_CART = 'redirect_to_cart';
    const REDIRECT_TO_CHECKOUT_SHIPPING = 'redirect_to_checkout_shipping';
    const REDIRECT_TO_CHECKOUT_PAYMENT = 'redirect_to_checkout_payment';

    public function toOptionArray()
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
