<?php

namespace Mollie\Payment\Test\Unit\Model\Methods;

use Mollie\Payment\Model\Methods\Przelewy24;
use Mollie\Payment\Test\Unit\Model\Methods\AbstractMethodTest;

class Przelewy24Test extends AbstractMethodTest
{
    protected $instance = Przelewy24::class;

    protected $code = 'przelewy24';
}
