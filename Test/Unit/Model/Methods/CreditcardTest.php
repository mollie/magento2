<?php

namespace Mollie\Payment\Test\Unit\Model\Methods;

use Mollie\Payment\Model\Methods\Creditcard;
use Mollie\Payment\Test\Unit\Model\Methods\AbstractMethodTest;

class CreditcardTest extends AbstractMethodTest
{
    protected $instance = Creditcard::class;

    protected $code = 'creditcard';
}
