<?php

namespace Mollie\Payment\Model\Methods;

use Mollie\Payment\Tests\Unit\Model\Methods\AbstractMethodTest;

class PaypalTest extends AbstractMethodTest
{
    protected $instance = Paypal::class;

    protected $code = 'paypal';
}
