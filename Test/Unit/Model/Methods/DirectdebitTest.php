<?php

namespace Mollie\Payment\Model\Methods;

use Mollie\Payment\Test\Unit\Model\Methods\AbstractMethodTest;

class CreditcardTest extends AbstractMethodTest
{
    protected $instance = Directdebit::class;

    protected $code = 'directdebit';
}
