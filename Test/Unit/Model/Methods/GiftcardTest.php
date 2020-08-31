<?php

namespace Mollie\Payment\Test\Unit\Model\Methods;

use Mollie\Payment\Model\Methods\Giftcard;
use Mollie\Payment\Test\Unit\Model\Methods\AbstractMethodTest;

class GiftcardTest extends AbstractMethodTest
{
    protected $instance = Giftcard::class;

    protected $code = 'giftcard';
}
