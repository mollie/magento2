<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Model\Adminhtml\Source;

use Magento\Framework\Data\OptionSourceInterface;

class ApplePayButtonText implements OptionSourceInterface
{
    public const BUY = 'buy';
    public const DONATE = 'donate';
    public const PLAIN = 'plain';
    public const BOOK = 'book';
    public const CHECK_OUT = 'check-out';
    public const SUBSCRIBE = 'subscribe';
    public const ADD_MONEY = 'add-money';
    public const CONTRIBUTE = 'contribute';
    public const ORDER = 'order';
    public const RELOAD = 'reload';
    public const RENT = 'rent';
    public const SUPPORT = 'support';
    public const TIP = 'tip';
    public const TOP_UP = 'top-up';
    public const NONE = '';

    public function toOptionArray(): array
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
