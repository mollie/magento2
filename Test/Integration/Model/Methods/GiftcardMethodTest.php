<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Test\Integration\Model\Methods;

use Mollie\Payment\Model\Methods\Giftcard;

class GiftcardMethodTest extends AbstractTestMethod
{
    protected $instance = Giftcard::class;

    protected $code = 'giftcard';
}
