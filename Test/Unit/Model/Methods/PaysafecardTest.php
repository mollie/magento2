<?php

namespace Mollie\Payment\Test\Unit\Model\Methods;

use Mollie\Payment\Model\Methods\Paysafecard;
use Mollie\Payment\Test\Unit\Model\Methods\AbstractMethodTest;

class PaysafecardTest extends AbstractMethodTest
{
    protected $instance = Paysafecard::class;

    protected $code = 'paysafecard';
}
