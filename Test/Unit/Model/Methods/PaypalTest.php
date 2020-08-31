<?php

namespace Mollie\Payment\Test\Unit\Model\Methods;

use Mollie\Payment\Model\Methods\Paypal;
use Mollie\Payment\Test\Unit\Model\Methods\AbstractMethodTest;

class PaypalTest extends AbstractMethodTest
{
    protected $instance = Paypal::class;

    protected $code = 'paypal';
}
