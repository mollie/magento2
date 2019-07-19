<?php

namespace Mollie\Payment\Model\Methods;

use Mollie\Payment\Test\Unit\Model\Methods\AbstractMethodTest;

class GiropayTest extends AbstractMethodTest
{
    protected $instance = Giropay::class;

    protected $code = 'giropay';
}
