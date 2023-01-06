<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Model\Adminhtml\Source;

use Magento\Framework\Data\OptionSourceInterface;

class ApplePayButtonText implements OptionSourceInterface
{
    const BUY = 'buy';
    const DONATE = 'donate';
    const PLAIN = 'plain';
    const BOOK = 'book';
    const CHECK_OUT = 'check-out';
    const SUBSCRIBE = 'subscribe';
    const ADD_MONEY = 'add-money';
    const CONTRIBUTE = 'contribute';
    const ORDER = 'order';
    const RELOAD = 'reload';
    const RENT = 'rent';
    const SUPPORT = 'support';
    const TIP = 'tip';
    const TOP_UP = 'top-up';
    const NONE = '';

    public function toOptionArray()
    {
        return [
            [
                'value' => static::BUY,
                'label' => __('Buy'),
            ],
            [
                'value' => static::DONATE,
                'label' => __('Donate'),
            ],
            [
                'value' => static::PLAIN,
                'label' => __('Plain'),
            ],
            [
                'value' => static::BOOK,
                'label' => __('Book'),
            ],
            [
                'value' => static::CHECK_OUT,
                'label' => __('Check out'),
            ],
            [
                'value' => static::SUBSCRIBE,
                'label' => __('Subscribe'),
            ],
            [
                'value' => static::ADD_MONEY,
                'label' => __('Add money'),
            ],
            [
                'value' => static::CONTRIBUTE,
                'label' => __('Contribute'),
            ],
            [
                'value' => static::ORDER,
                'label' => __('Order'),
            ],
            [
                'value' => static::RELOAD,
                'label' => __('Reload'),
            ],
            [
                'value' => static::RENT,
                'label' => __('Rent'),
            ],
            [
                'value' => static::SUPPORT,
                'label' => __('Support'),
            ],
            [
                'value' => static::TIP,
                'label' => __('Tip'),
            ],
            [
                'value' => static::TOP_UP,
                'label' => __('Top up'),
            ],
            [
                'value' => static::NONE,
                'label' => __('None'),
            ],
        ];
    }
}
