<?php

namespace Mollie\Payment\Test\Unit\Model\Methods;

use Mollie\Payment\Model\Methods\Directdebit;
use Mollie\Payment\Test\Unit\Model\Methods\AbstractMethodTest;

class DirectdebitTest extends AbstractMethodTest
{
    protected $instance = Directdebit::class;

    protected $code = 'directdebit';
}
