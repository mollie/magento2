<?php

namespace Mollie\Payment\Model\Methods;

use Mollie\Payment\Test\Unit\Model\Methods\AbstractMethodTest;

class PaysafecardTest extends AbstractMethodTest
{
    protected $instance = Paysafecard::class;

    protected $code = 'paysafecard';
}
