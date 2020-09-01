<?php

namespace Mollie\Payment\Test\Integration\Model\Methods;

use Mollie\Payment\Model\Methods\Giftcard;

class GiftcardTest extends AbstractMethodTest
{
    protected $instance = Giftcard::class;

    protected $code = 'giftcard';
}
