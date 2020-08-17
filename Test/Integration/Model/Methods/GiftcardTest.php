<?php

namespace Mollie\Payment\Model\Methods;

use Mollie\Payment\Test\Integration\Model\Methods\AbstractMethodTest;

class GiftcardTest extends AbstractMethodTest
{
    protected $instance = Giftcard::class;

    protected $code = 'giftcard';
}
