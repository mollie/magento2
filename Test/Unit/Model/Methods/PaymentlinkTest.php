<?php

namespace Mollie\Payment\Test\Unit\Model\Methods;

use Mollie\Payment\Model\Methods\Paymentlink;
use Mollie\Payment\Test\Unit\Model\Methods\AbstractMethodTest;

class PaymentlinkTest extends AbstractMethodTest
{
    protected $instance = Paymentlink::class;

    protected $code = 'paymentlink';
}
