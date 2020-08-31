<?php

namespace Mollie\Payment\Test\Unit\Model\Methods;

use Mollie\Payment\Model\Methods\Giropay;
use Mollie\Payment\Test\Unit\Model\Methods\AbstractMethodTest;

class GiropayTest extends AbstractMethodTest
{
    protected $instance = Giropay::class;

    protected $code = 'giropay';
}
